<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Provider;
use App\Models\Visit;
use App\Services\ClaimPacketGenerator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function __construct(
        private readonly ClaimPacketGenerator $claimPacketGenerator
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $year = now()->year;

        $patients = Patient::query()
            ->with([
                'latestVisit',
                'visitsThisYear' => fn ($query) => $query->orderBy('service_date')->orderBy('id'),
            ])
            ->when($search !== '', fn (Builder $query) => $this->applyPatientSearch($query, $search))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('patients.index', compact('patients', 'search', 'year'));
    }

    public function create(): View
    {
        return view('patients.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'philhealth_pin' => $this->normalizePin($request->input('philhealth_pin')),
            'member_pin' => $this->normalizePin($request->input('member_pin')),
        ]);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'name_ext' => ['nullable', 'string', 'max:32'],
            'dob' => ['required', 'date'],
            'sex' => ['required', 'string', 'in:MALE,FEMALE'],
            'philhealth_pin' => ['nullable', 'digits:12'],
            'is_member' => ['nullable', 'boolean'],
            'member_first_name' => ['nullable', 'string', 'max:255'],
            'member_middle_name' => ['nullable', 'string', 'max:255'],
            'member_last_name' => ['nullable', 'string', 'max:255'],
            'member_name_ext' => ['nullable', 'string', 'max:32'],
            'member_pin' => ['nullable', 'digits:12'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'claim_signatory' => ['nullable', 'string', 'in:patient,guardian'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_relationship' => ['nullable', 'string', 'max:32'],
            'guardian_contact_number' => ['nullable', 'string', 'max:30'],
            'relationship' => ['nullable', 'string', 'max:32'],
        ]);

        $this->normalizeGuardianFields($validated);

        $validated['is_member'] = $request->boolean('is_member');
        $validated['member_id'] = $validated['is_member']
            ? null
            : $this->resolveMemberId($validated);

        $patient = Patient::create($validated);

        if (! $patient->visits()->exists()) {
            $provider = Provider::query()->orderBy('id')->first();

            if ($provider) {
                $visit = Visit::create([
                    'patient_id' => $patient->id,
                    'provider_id' => $provider->id,
                    'service_date' => now()->toDateString(),
                    'claim_code' => 'OPH01',
                ]);

                $this->claimPacketGenerator->ensureReferralCode($visit);
            }
        }

        return redirect()->route('patients.index')->with('status', 'Patient created.');
    }

    public function show(Patient $patient): View
    {
        $patient->load([
            'member',
            'dependents',
            'visits' => fn ($query) => $query->with('provider')->orderByDesc('service_date')->orderByDesc('id'),
        ]);

        return view('patients.show', compact('patient'));
    }

    public function edit(Patient $patient): View
    {
        $patient->loadMissing('member');

        return view('patients.edit', compact('patient'));
    }

    public function update(Request $request, Patient $patient): RedirectResponse
    {
        $request->merge([
            'philhealth_pin' => $this->normalizePin($request->input('philhealth_pin')),
            'member_pin' => $this->normalizePin($request->input('member_pin')),
        ]);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'name_ext' => ['nullable', 'string', 'max:32'],
            'dob' => ['required', 'date'],
            'sex' => ['required', 'string', 'in:MALE,FEMALE'],
            'philhealth_pin' => ['nullable', 'digits:12'],
            'is_member' => ['nullable', 'boolean'],
            'member_first_name' => ['nullable', 'string', 'max:255'],
            'member_middle_name' => ['nullable', 'string', 'max:255'],
            'member_last_name' => ['nullable', 'string', 'max:255'],
            'member_name_ext' => ['nullable', 'string', 'max:32'],
            'member_pin' => ['nullable', 'digits:12'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'claim_signatory' => ['nullable', 'string', 'in:patient,guardian'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_relationship' => ['nullable', 'string', 'max:32'],
            'guardian_contact_number' => ['nullable', 'string', 'max:30'],
            'relationship' => ['nullable', 'string', 'max:32'],
        ]);

        $this->normalizeGuardianFields($validated);

        $validated['is_member'] = $request->boolean('is_member');
        $validated['member_id'] = $validated['is_member']
            ? null
            : $this->resolveMemberId($validated, $patient->id);

        $patient->update($validated);

        return redirect()->route('patients.index')->with('status', 'Patient updated.');
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        $patient->delete();

        return redirect()->route('patients.index')->with('status', 'Patient deleted.');
    }

    /**
     * Filter patients by name, PIN, contact number, or address.
     *
     * Every whitespace-separated term must match one of the searchable
     * columns, so "juan cruz" finds "Juan Dela Cruz".
     */
    private function applyPatientSearch(Builder $query, string $search): void
    {
        $terms = preg_split('/\s+/', $search) ?: [];

        foreach ($terms as $term) {
            if ($term === '') {
                continue;
            }

            $like = '%'.$term.'%';
            $digits = preg_replace('/\D+/', '', $term) ?? '';

            $query->where(function (Builder $sub) use ($like, $digits): void {
                $sub->where('first_name', 'like', $like)
                    ->orWhere('middle_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('name_ext', 'like', $like)
                    ->orWhere('contact_number', 'like', $like)
                    ->orWhere('barangay', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('province', 'like', $like)
                    ->orWhere('philhealth_pin', 'like', $like);

                if ($digits !== '') {
                    // PINs are stored as 99-999999999-9, so also match a
                    // typed PIN with the separators removed.
                    $sub->orWhereRaw(
                        "REPLACE(REPLACE(philhealth_pin, '-', ''), ' ', '') LIKE ?",
                        ['%'.$digits.'%']
                    );
                }
            });
        }
    }

    /**
     * Try to link a dependent to an existing member patient by PIN or exact name.
     *
     * @param  array<string, mixed>  $member
     */
    private function resolveMemberId(array $member, ?int $excludeId = null): ?int
    {
        $pin = $member['member_pin'] ?? null;
        $name = $this->normalizeName(implode(' ', array_filter([
            $member['member_first_name'] ?? null,
            $member['member_middle_name'] ?? null,
            $member['member_last_name'] ?? null,
            $member['member_name_ext'] ?? null,
        ], fn ($part) => filled($part))));

        if (blank($pin) && $name === '') {
            return null;
        }

        $members = Patient::query()
            ->where('is_member', true)
            ->when($excludeId, fn ($query) => $query->whereKeyNot($excludeId))
            ->get();

        if (filled($pin)) {
            foreach ($members as $memberRecord) {
                $memberPin = preg_replace('/\D+/', '', (string) $memberRecord->philhealth_pin) ?? '';

                if ($memberPin === $pin) {
                    return $memberRecord->id;
                }
            }

            // A supplied PIN is the reliable key; do not fall back to a name
            // match when it does not match an existing member.
            return null;
        }

        if ($name !== '') {
            foreach ($members as $memberRecord) {
                if ($this->normalizeName($memberRecord->fullName()) === $name) {
                    return $memberRecord->id;
                }
            }
        }

        return null;
    }

    private function normalizeName(?string $name): string
    {
        return Str::lower(trim(preg_replace('/\s+/', ' ', (string) $name) ?? ''));
    }

    private function normalizePin(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        return $digits === '' ? null : $digits;
    }

    /**
     * Keep the signatory choice consistent with the patient's age.
     *
     * @param  array<string, mixed>  $validated
     */
    private function normalizeGuardianFields(array &$validated): void
    {
        $maxAge = (int) config('patients.minor_max_age', 13);
        $age = Carbon::parse($validated['dob'])->age;
        $isMinor = $age >= 0 && $age <= $maxAge;

        if (! $isMinor || ($validated['claim_signatory'] ?? 'patient') !== 'guardian') {
            $validated['claim_signatory'] = 'patient';

            return;
        }

        $errors = [];

        if (blank($validated['guardian_name'] ?? null)) {
            $errors['guardian_name'] = 'Guardian full name is required when the guardian signs the claim.';
        }

        if (blank($validated['guardian_relationship'] ?? null)) {
            $errors['guardian_relationship'] = 'Guardian relationship is required when the guardian signs the claim.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $validated['claim_signatory'] = 'guardian';
    }
}
