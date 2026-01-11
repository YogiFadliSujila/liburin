<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'trip_id',
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Action constants
     */
    public const ACTION_PAYMENT_RECEIVED = 'payment_received';
    public const ACTION_WITHDRAWAL_REQUESTED = 'withdrawal_requested';
    public const ACTION_WITHDRAWAL_APPROVED = 'withdrawal_approved';
    public const ACTION_WITHDRAWAL_REJECTED = 'withdrawal_rejected';
    public const ACTION_MEMBER_JOINED = 'member_joined';
    public const ACTION_MEMBER_LEFT = 'member_left';
    public const ACTION_EXPENSE_RECORDED = 'expense_recorded';
    public const ACTION_TRIP_CREATED = 'trip_created';
    public const ACTION_TRIP_UPDATED = 'trip_updated';
    public const ACTION_DESTINATION_ADDED = 'destination_added';
    public const ACTION_VOTE_CAST = 'vote_cast';

    /**
     * Get the trip.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the auditable model (polymorphic).
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get human-readable action label.
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_PAYMENT_RECEIVED => 'Pembayaran diterima',
            self::ACTION_WITHDRAWAL_REQUESTED => 'Permintaan penarikan',
            self::ACTION_WITHDRAWAL_APPROVED => 'Penarikan disetujui',
            self::ACTION_WITHDRAWAL_REJECTED => 'Penarikan ditolak',
            self::ACTION_MEMBER_JOINED => 'Anggota bergabung',
            self::ACTION_MEMBER_LEFT => 'Anggota keluar',
            self::ACTION_EXPENSE_RECORDED => 'Pengeluaran dicatat',
            self::ACTION_TRIP_CREATED => 'Trip dibuat',
            self::ACTION_TRIP_UPDATED => 'Trip diperbarui',
            self::ACTION_DESTINATION_ADDED => 'Destinasi ditambahkan',
            self::ACTION_VOTE_CAST => 'Vote diberikan',
            default => $this->action,
        };
    }
}
