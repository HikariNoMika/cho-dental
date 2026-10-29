<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visit extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'patient_id',
        'provider_id',
        'service_date',
        'claim_code',
        'referral_code',
        'working_dx',
        'final_dx',
        'disposition',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'service_date' => 'date',
        ];
    }

    /**
     * The patient/claimant for this visit.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * The dentist/provider who rendered the service.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * The dental services rendered during the visit.
     */
    public function dentalServices(): HasMany
    {
        return $this->hasMany(DentalService::class);
    }

    /**
     * Claim codes available for a dental visit.
     *
     * @return list<string>
     */
    public static function claimCodes(): array
    {
        return array_keys((array) config('claims.visit_codes', []));
    }

    /**
     * The 1st or 2nd service number for a claim code, or null when unknown.
     */
    public static function visitNumberForCode(?string $code): ?int
    {
        if (blank($code)) {
            return null;
        }

        $number = config('claims.visit_codes.'.strtoupper(trim($code)));

        return is_numeric($number) ? (int) $number : null;
    }

    /**
     * The 1st or 2nd service number represented by this visit.
     */
    public function visitNumber(): ?int
    {
        if ($this->claim_code) {
            return self::visitNumberForCode($this->claim_code);
        }

        return null;
    }

    /**
     * Human-readable visit number, e.g. "1st visit".
     */
    public function visitNumberLabel(): string
    {
        return match ($this->visitNumber()) {
            1 => '1st visit',
            2 => '2nd visit',
            default => 'Visit',
        };
    }
}
