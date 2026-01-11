<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Destination extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'trip_id',
        'name',
        'description',
        'location',
        'location_url',
        'visit_date',
        'start_time',
        'end_time',
        'order',
        'estimated_cost',
        'category',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'estimated_cost' => 'decimal:2',
    ];

    /**
     * Category constants
     */
    public const CATEGORY_ATTRACTION = 'attraction';
    public const CATEGORY_FOOD = 'food';
    public const CATEGORY_TRANSPORT = 'transport';
    public const CATEGORY_ACCOMMODATION = 'accommodation';
    public const CATEGORY_OTHER = 'other';

    /**
     * Get the trip.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get formatted time range.
     */
    public function getTimeRangeAttribute(): ?string
    {
        if (!$this->start_time) {
            return null;
        }

        $start = $this->start_time->format('H:i');
        $end = $this->end_time ? $this->end_time->format('H:i') : '?';
        
        return "{$start} - {$end}";
    }
}
