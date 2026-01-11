<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Withdrawal extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'trip_id',
        'requested_by',
        'amount',
        'reason',
        'description',
        'status',
        'votes_required',
        'votes_approve',
        'votes_reject',
        'voting_deadline',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'voting_deadline' => 'datetime',
        'processed_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the trip.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the user who requested the withdrawal.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get all votes for this withdrawal.
     */
    public function votes(): HasMany
    {
        return $this->hasMany(WithdrawalVote::class);
    }

    /**
     * Check if user has voted.
     */
    public function hasVoted(User $user): bool
    {
        return $this->votes()->where('user_id', $user->id)->exists();
    }

    /**
     * Get user's vote.
     */
    public function getUserVote(User $user): ?WithdrawalVote
    {
        return $this->votes()->where('user_id', $user->id)->first();
    }

    /**
     * Get approval progress percentage.
     */
    public function getApprovalProgressAttribute(): float
    {
        if ($this->votes_required <= 0) {
            return 0;
        }
        return min(100, ($this->votes_approve / $this->votes_required) * 100);
    }

    /**
     * Get total votes cast.
     */
    public function getTotalVotesAttribute(): int
    {
        return $this->votes_approve + $this->votes_reject;
    }

    /**
     * Check if voting is still open.
     */
    public function isVotingOpen(): bool
    {
        return $this->status === self::STATUS_PENDING 
            && $this->voting_deadline->isFuture();
    }

    /**
     * Check if withdrawal is approved.
     */
    public function isApproved(): bool
    {
        return $this->votes_approve >= $this->votes_required;
    }

    /**
     * Process the vote result.
     */
    public function processVoteResult(): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            return;
        }

        if ($this->isApproved()) {
            $this->update([
                'status' => self::STATUS_APPROVED,
                'processed_at' => now(),
            ]);
        } elseif ($this->voting_deadline->isPast()) {
            $this->update([
                'status' => self::STATUS_REJECTED,
                'processed_at' => now(),
            ]);
        }
    }
}
