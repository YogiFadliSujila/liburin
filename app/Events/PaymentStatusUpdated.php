<?php

namespace App\Events;

use App\Models\Savings;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Savings $savings
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('trip.' . $this->savings->trip_id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'savings_id' => $this->savings->id,
            'user_id' => $this->savings->user_id,
            'user_name' => $this->savings->user->name,
            'amount' => (float) $this->savings->amount,
            'payment_status' => $this->savings->payment_status,
            'paid_at' => $this->savings->paid_at?->format('Y-m-d H:i:s'),
            'total_savings' => $this->savings->trip->total_savings,
            'savings_progress' => round($this->savings->trip->savings_progress, 1),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'payment.updated';
    }
}
