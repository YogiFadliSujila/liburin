<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trip extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'destination',
        'start_date',
        'end_date',
        'target_amount',
        'status',
        'cover_image',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'target_amount' => 'decimal:2',
    ];

    /**
     * Status constants
     */
    public const STATUS_PLANNING = 'planning';
    public const STATUS_SAVING = 'saving';
    public const STATUS_ONGOING = 'ongoing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the user who created the trip.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all members of the trip.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'trip_members')
            ->withPivot(['id', 'role', 'status', 'joined_at'])
            ->withTimestamps()
            ->withCasts(['joined_at' => 'datetime']);
    }

    /**
     * Get active members only.
     */
    public function activeMembers(): BelongsToMany
    {
        return $this->members()->wherePivot('status', 'active');
    }

    /**
     * Get trip member pivot records.
     */
    public function tripMembers(): HasMany
    {
        return $this->hasMany(TripMember::class);
    }

    /**
     * Get all destinations/itinerary for the trip.
     */
    public function destinations(): HasMany
    {
        return $this->hasMany(Destination::class)->orderBy('visit_date')->orderBy('order');
    }

    /**
     * Get all savings/payments for the trip.
     */
    public function savings(): HasMany
    {
        return $this->hasMany(Savings::class);
    }

    /**
     * Get all expenses for the trip.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get all withdrawals for the trip.
     */
    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    /**
     * Get all audit logs for the trip.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Get total savings collected (successful payments only).
     */
    public function getTotalSavingsAttribute(): float
    {
        return (float) $this->savings()
            ->where('payment_status', 'success')
            ->sum('amount');
    }

    /**
     * Get savings progress percentage.
     */
    public function getSavingsProgressAttribute(): float
    {
        if ($this->target_amount <= 0) {
            return 0;
        }
        return min(100, ($this->total_savings / $this->target_amount) * 100);
    }

    /**
     * Get total expenses.
     */
    public function getTotalExpensesAttribute(): float
    {
        return (float) $this->expenses()->sum('amount');
    }

    /**
     * Get remaining balance.
     */
    public function getRemainingBalanceAttribute(): float
    {
        return $this->total_savings - $this->total_expenses;
    }

    /**
     * Check if user is a member of this trip.
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if user is an admin of this trip.
     */
    public function isAdmin(User $user): bool
    {
        return $this->tripMembers()
            ->where('user_id', $user->id)
            ->where('role', 'admin')
            ->where('status', 'active')
            ->exists();
    }
}
