<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log an action to the audit trail.
     */
    public function log(
        Trip $trip,
        string $action,
        Model $auditable,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        return AuditLog::create([
            'trip_id' => $trip->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => get_class($auditable),
            'auditable_id' => $auditable->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Log payment received.
     */
    public function logPaymentReceived(Trip $trip, Model $savings): AuditLog
    {
        return $this->log(
            $trip,
            AuditLog::ACTION_PAYMENT_RECEIVED,
            $savings,
            null,
            [
                'amount' => $savings->amount,
                'method' => $savings->payment_method,
                'user' => $savings->user->name,
            ]
        );
    }

    /**
     * Log member joined.
     */
    public function logMemberJoined(Trip $trip, Model $tripMember): AuditLog
    {
        return $this->log(
            $trip,
            AuditLog::ACTION_MEMBER_JOINED,
            $tripMember,
            null,
            [
                'user' => $tripMember->user->name,
                'role' => $tripMember->role,
            ]
        );
    }

    /**
     * Log withdrawal request.
     */
    public function logWithdrawalRequested(Trip $trip, Model $withdrawal): AuditLog
    {
        return $this->log(
            $trip,
            AuditLog::ACTION_WITHDRAWAL_REQUESTED,
            $withdrawal,
            null,
            [
                'amount' => $withdrawal->amount,
                'reason' => $withdrawal->reason,
                'requester' => $withdrawal->requester->name,
            ]
        );
    }

    /**
     * Log expense recorded.
     */
    public function logExpenseRecorded(Trip $trip, Model $expense): AuditLog
    {
        return $this->log(
            $trip,
            AuditLog::ACTION_EXPENSE_RECORDED,
            $expense,
            null,
            [
                'amount' => $expense->amount,
                'category' => $expense->category,
                'description' => $expense->description,
            ]
        );
    }

    /**
     * Log trip created.
     */
    public function logTripCreated(Trip $trip): AuditLog
    {
        return $this->log(
            $trip,
            AuditLog::ACTION_TRIP_CREATED,
            $trip,
            null,
            [
                'name' => $trip->name,
                'destination' => $trip->destination,
                'target_amount' => $trip->target_amount,
            ]
        );
    }
}
