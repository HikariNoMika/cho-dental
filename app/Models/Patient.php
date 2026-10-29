<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Patient extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'name_ext',
        'dob',
        'sex',
        'philhealth_pin',
        'is_member',
        'member_id',
        'member_first_name',
        'member_middle_name',
        'member_last_name',
        'member_name_ext',
        'member_pin',
        'barangay',
        'city',
        'province',
        'region',
        'contact_number',
        'claim_signatory',
        'guardian_name',
        'guardian_relationship',
        'guardian_contact_number',
        'relationship',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'is_member' => 'boolean',
        ];
    }

    /**
     * The member patient that owns this dependent.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(self::class, 'member_id');
    }

    /**
     * A lightweight member profile built from the manual member fields.
     *
     * This is used for dependents whose member is not registered as a patient.
     */
    public function manualMember(): ?self
    {
        if (
            blank($this->member_first_name)
            && blank($this->member_middle_name)
            && blank($this->member_last_name)
            && blank($this->member_name_ext)
            && blank($this->member_pin)
        ) {
            return null;
        }

        return new self([
            'first_name' => $this->member_first_name,
            'middle_name' => $this->member_middle_name,
            'last_name' => $this->member_last_name,
            'name_ext' => $this->member_name_ext,
            'philhealth_pin' => $this->member_pin,
        ]);
    }

    /**
     * The linked member record, or the manual member details.
     */
    public function memberProfile(): ?self
    {
        return $this->member ?? $this->manualMember();
    }

    /**
     * The dependents linked to this member patient.
     */
    public function dependents(): HasMany
    {
        return $this->hasMany(self::class, 'member_id');
    }

    /**
     * The dental visits of this patient.
     */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    /**
     * The dental visits of this patient in the current calendar year.
     */
    public function visitsThisYear(): HasMany
    {
        return $this->visits()->whereYear('service_date', now()->year);
    }

    /**
     * The most recent dental visit, used for the quick print action.
     */
    public function latestVisit(): HasOne
    {
        return $this->hasOne(Visit::class)->latestOfMany('service_date');
    }

    /**
     * Printable full name: First Middle Last Ext.
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->name_ext,
        ], fn ($part) => filled($part));

        return trim(preg_replace('/\s+/', ' ', implode(' ', $parts)) ?? '');
    }

    public function fullName(): string
    {
        return $this->getFullNameAttribute();
    }

    /**
     * Store the PIN in the standard 99-999999999-9 format.
     */
    public function setPhilhealthPinAttribute(?string $value): void
    {
        $this->attributes['philhealth_pin'] = $this->formatPhilhealthPin($value);
    }

    /**
     * Return the PIN in the standard format, even for older raw records.
     */
    public function philhealthPin(): ?string
    {
        return $this->formatPhilhealthPin($this->attributes['philhealth_pin'] ?? null);
    }

    private function formatPhilhealthPin(?string $pin): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $pin) ?? '';

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 12) {
            return substr($digits, 0, 2)
                .'-'.substr($digits, 2, 9)
                .'-'.substr($digits, 11, 1);
        }

        return $digits;
    }

    /**
     * Age in whole years, or null when the birth date is missing/invalid.
     */
    public function getAgeAttribute(): ?int
    {
        return $this->dob?->age;
    }

    /**
     * Whether this patient is within the guardian-signature age range.
     */
    public function isMinor(): bool
    {
        $maxAge = (int) config('patients.minor_max_age', 13);

        return $this->age !== null && $this->age <= $maxAge;
    }

    /**
     * Name that should appear on the Claim Form 2 signature line.
     */
    public function claimSignatoryName(): string
    {
        if ($this->claim_signatory === 'guardian' && filled($this->guardian_name)) {
            return trim(preg_replace('/\s+/', ' ', (string) $this->guardian_name) ?? '');
        }

        return $this->fullName();
    }
}
