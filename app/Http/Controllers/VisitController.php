<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Provider;
use App\Models\Visit;
use App\Services\ClaimPacketGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VisitController extends Controller
{
    public function __construct(
        private readonly ClaimPacketGenerator $claimPacketGenerator
    ) {}

    /**
     * Show the form for recording another service for a patient.
     */
    public function create(Patient $patient): View
    {
        $visitsThisYear = $patient->visitsThisYear()
            ->orderBy('service_date')
            ->orderBy('id')
            ->get();

        $nextVisitNumber = $visitsThisYear->count() === 0 ? 1 : 2;
        $suggestedCode = $this->defaultCodeFor($nextVisitNumber);

        return view('visits.create', [
            'patient' => $patient,
            'providers' => $this->providers(),
            'visitCodes' => Visit::claimCodes(),
            'visitsThisYear' => $visitsThisYear,
            'suggestedCode' => $suggestedCode,
            'year' => now()->year,
        ]);
    }

    /**
     * Store a newly recorded service for a patient.
     */
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $validated = $this->validated($request);

        $visit = $patient->visits()->create($validated);

        /*
         * Allocate the daily referral/transaction code right away so the
         * visit is never shown with an empty "Referral Code" (especially the
         * second service of the year).
         */
        $this->claimPacketGenerator->ensureReferralCode($visit);

        return redirect()
            ->route('patients.show', $patient)
            ->with('status', 'Visit recorded for '.$visit->service_date->format('M d, Y').'.');
    }

    /**
     * Edit a recorded visit, e.g. to correct its claim code.
     */
    public function edit(Visit $visit): View
    {
        $visit->load('patient');

        return view('visits.edit', [
            'visit' => $visit,
            'patient' => $visit->patient,
            'providers' => $this->providers(),
            'visitCodes' => Visit::claimCodes(),
        ]);
    }

    /**
     * Update a recorded visit.
     */
    public function update(Request $request, Visit $visit): RedirectResponse
    {
        $visit->update($this->validated($request));

        // Older visits may predate automatic referral codes.
        $this->claimPacketGenerator->ensureReferralCode($visit);

        return redirect()
            ->route('patients.show', $visit->patient_id)
            ->with('status', 'Visit updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $request->merge([
            'claim_code' => strtoupper(trim((string) $request->input('claim_code'))),
        ]);

        return $request->validate([
            'service_date' => ['required', 'date'],
            'provider_id' => ['required', 'integer', 'exists:providers,id'],
            'claim_code' => ['required', 'string', Rule::in(Visit::claimCodes())],
            'working_dx' => ['nullable', 'string', 'max:2000'],
            'final_dx' => ['nullable', 'string', 'max:2000'],
            'disposition' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Provider>
     */
    private function providers()
    {
        return Provider::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Pick a sensible default claim code for a 1st or 2nd service.
     */
    private function defaultCodeFor(int $visitNumber): ?string
    {
        foreach ((array) config('claims.visit_codes', []) as $code => $number) {
            if ((int) $number === $visitNumber) {
                return $code;
            }
        }

        return null;
    }
}
