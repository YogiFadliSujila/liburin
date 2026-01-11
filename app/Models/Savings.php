<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Savings extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'trip_id',
        'user_id',
        'amount',
        'payment_method',
        'payment_status',
        'transaction_id',
        'midtrans_order_id',
        'payment_details',
        'notes',
        'paid_at',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_details' => 'array',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Payment method constants
     */
    public const METHOD_QRIS = 'qris';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_MANUAL = 'manual';

    /**
     * Payment status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';

    /**
     * Get the trip.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the user who made the payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if payment is successful.
     */
    public function isSuccessful(): bool
    {
        return $this->payment_status === self::STATUS_SUCCESS;
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return $this->payment_status === self::STATUS_PENDING;
    }

    /**
     * Check if payment has expired.
     */
    public function isExpired(): bool
    {
        if ($this->payment_status === self::STATUS_EXPIRED) {
            return true;
        }

        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Mark payment as successful.
     */
    public function markAsSuccess(string $transactionId, array $details = []): void
    {
        $this->update([
            'payment_status' => self::STATUS_SUCCESS,
            'transaction_id' => $transactionId,
            'payment_details' => array_merge($this->payment_details ?? [], $details),
            'paid_at' => now(),
        ]);
    }

    /**
     * Mark payment as failed.
     */
    public function markAsFailed(array $details = []): void
    {
        $this->update([
            'payment_status' => self::STATUS_FAILED,
            'payment_details' => array_merge($this->payment_details ?? [], $details),
        ]);
    }
}
