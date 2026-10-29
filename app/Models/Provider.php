<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
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
        'credentials',
        'pan',
        'signature_path',
    ];

    /**
     * The visits handled by this dentist/provider.
     */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
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
     * Name shown to users and printed on forms, including credentials.
     */
    public function displayName(): string
    {
        return trim($this->fullName().(
            filled($this->credentials) ? ', '.$this->credentials : ''
        ));
    }
}
