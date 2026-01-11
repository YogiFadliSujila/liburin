<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'withdrawal_id',
        'user_id',
        'approved',
        'comment',
    ];

    protected $casts = [
        'approved' => 'boolean',
    ];

    /**
     * Get the withdrawal.
     */
    public function withdrawal(): BelongsTo
    {
        return $this->belongsTo(Withdrawal::class);
    }

    /**
     * Get the user who voted.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
