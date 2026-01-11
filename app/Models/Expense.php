<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'trip_id',
        'recorded_by',
        'category',
        'description',
        'amount',
        'expense_date',
        'receipt_image',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    /**
     * Category constants
     */
    public const CATEGORY_TRANSPORT = 'transport';
    public const CATEGORY_FOOD = 'food';
    public const CATEGORY_ACCOMMODATION = 'accommodation';
    public const CATEGORY_TICKET = 'ticket';
    public const CATEGORY_SHOPPING = 'shopping';
    public const CATEGORY_OTHER = 'other';

    /**
     * Get available categories.
     */
    public static function categories(): array
    {
        return [
            self::CATEGORY_TRANSPORT => 'Transportasi',
            self::CATEGORY_FOOD => 'Makanan',
            self::CATEGORY_ACCOMMODATION => 'Akomodasi',
            self::CATEGORY_TICKET => 'Tiket',
            self::CATEGORY_SHOPPING => 'Belanja',
            self::CATEGORY_OTHER => 'Lainnya',
        ];
    }

    /**
     * Get the trip.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the user who recorded the expense.
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Get category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::categories()[$this->category] ?? $this->category;
    }
}
