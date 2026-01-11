<?php

namespace App\Http\Controllers;

use App\Events\PaymentStatusUpdated;
use App\Models\AuditLog;
use App\Services\AuditService;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        protected MidtransService $midtransService,
        protected AuditService $auditService
    ) {}

    /**
     * Handle Midtrans payment notification.
     */
    public function midtrans(Request $request)
    {
        $notification = $request->all();

        Log::info('Midtrans webhook received', $notification);

        // Verify signature
        if (!$this->midtransService->verifySignature($notification)) {
            Log::warning('Midtrans webhook signature verification failed', $notification);
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 403);
        }

        // Process notification
        $savings = $this->midtransService->handleNotification($notification);

        if (!$savings) {
            Log::warning('Midtrans webhook: savings not found', ['order_id' => $notification['order_id'] ?? null]);
            return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
        }

        // Log audit and broadcast if payment successful
        if ($savings->payment_status === 'success') {
            $this->auditService->logPaymentReceived($savings->trip, $savings);
            
            // Broadcast real-time update
            broadcast(new PaymentStatusUpdated($savings))->toOthers();
        }

        Log::info('Midtrans webhook processed', [
            'order_id' => $notification['order_id'] ?? null,
            'status' => $savings->payment_status,
        ]);

        return response()->json(['status' => 'ok']);
    }
}

