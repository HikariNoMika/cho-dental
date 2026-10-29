<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DentalService extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'visit_id',
        'tooth_number',
        'service_code',
    ];

    /**
     * The visit this service belongs to.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }
}
