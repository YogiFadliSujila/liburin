<?php

namespace App\Services;

use App\Models\Savings;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\CoreApi;
use Midtrans\Notification;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');

        // BYPASS SSL VERIFICATION for Laragon/Sandbox environment & FIX UNDEFINED KEY 10023
        // 10023 = CURLOPT_HTTPHEADER. The SDK expects this key to exist.
        Config::$curlOptions = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER => [], // Fix for "Undefined array key 10023"
        ];
    }

    /**
     * Generate a unique order ID for Midtrans.
     */
    protected function generateOrderId(Trip $trip, User $user): string
    {
        return sprintf(
            'LBR-%s-%s-%s',
            strtoupper(Str::random(4)),
            substr($trip->id, -6),
            time()
        );
    }

    /**
     * Create a QRIS payment.
     */
    public function createQrisPayment(Savings $savings): array
    {
        $trip = $savings->trip;
        $user = $savings->user;

        $orderId = $this->generateOrderId($trip, $user);
        $savings->update(['midtrans_order_id' => $orderId]);

        $params = [
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $savings->amount,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ],
            'item_details' => [
                [
                    'id' => $trip->id,
                    'price' => (int) $savings->amount,
                    'quantity' => 1,
                    'name' => "Tabungan Trip: {$trip->name}",
                ],
            ],
            'qris' => [
                'acquirer' => 'gopay', // gopay, airpay shopee, etc.
            ],
            'custom_expiry' => [
                'order_time' => now()->format('Y-m-d H:i:s O'),
                'expiry_duration' => config('midtrans.expiry_duration'),
                'unit' => 'minute',
            ],
        ];

        try {
            $response = CoreApi::charge($params);

            $savings->update([
                'payment_details' => $response,
                'expires_at' => now()->addMinutes(config('midtrans.expiry_duration')),
            ]);

            return [
                'success' => true,
                'order_id' => $orderId,
                'qr_string' => $response->actions[0]->url ?? null,
                'transaction_id' => $response->transaction_id ?? null,
                'expires_at' => $savings->expires_at,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a Bank Transfer payment.
     */
    public function createBankTransfer(Savings $savings, string $bank = 'bca'): array
    {
        $trip = $savings->trip;
        $user = $savings->user;

        $orderId = $this->generateOrderId($trip, $user);
        $savings->update(['midtrans_order_id' => $orderId]);

        $params = [
            'payment_type' => 'bank_transfer',
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $savings->amount,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ],
            'item_details' => [
                [
                    'id' => $trip->id,
                    'price' => (int) $savings->amount,
                    'quantity' => 1,
                    'name' => "Tabungan Trip: {$trip->name}",
                ],
            ],
            'bank_transfer' => [
                'bank' => $bank,
            ],
            'custom_expiry' => [
                'order_time' => now()->format('Y-m-d H:i:s O'),
                'expiry_duration' => config('midtrans.expiry_duration'),
                'unit' => 'minute',
            ],
        ];

        try {
            $response = CoreApi::charge($params);

            $savings->update([
                'payment_details' => $response,
                'expires_at' => now()->addMinutes(config('midtrans.expiry_duration')),
            ]);

            // Extract VA number based on bank
            $vaNumber = null;
            if (isset($response->va_numbers) && count($response->va_numbers) > 0) {
                $vaNumber = $response->va_numbers[0]->va_number;
            } elseif (isset($response->permata_va_number)) {
                $vaNumber = $response->permata_va_number;
            }

            return [
                'success' => true,
                'order_id' => $orderId,
                'transaction_id' => $response->transaction_id ?? null,
                'bank' => $bank,
                'va_number' => $vaNumber,
                'expires_at' => $savings->expires_at,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create Snap Token for Snap payment popup.
     */
    public function createSnapToken(Savings $savings): array
    {
        $trip = $savings->trip;
        $user = $savings->user;

        $orderId = $this->generateOrderId($trip, $user);
        $savings->update(['midtrans_order_id' => $orderId]);

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $savings->amount,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ],
            'item_details' => [
                [
                    'id' => $trip->id,
                    'price' => (int) $savings->amount,
                    'quantity' => 1,
                    'name' => "Tabungan Trip: {$trip->name}",
                ],
            ],
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit' => 'minute',
                'duration' => config('midtrans.expiry_duration'),
            ],
        ];

        try {
            $snapToken = Snap::getSnapToken($params);

            $savings->update([
                'payment_details' => ['snap_token' => $snapToken],
                'expires_at' => now()->addMinutes(config('midtrans.expiry_duration')),
            ]);

            return [
                'success' => true,
                'order_id' => $orderId,
                'snap_token' => $snapToken,
                'client_key' => config('midtrans.client_key'),
                'expires_at' => $savings->expires_at,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify webhook signature.
     */
    public function verifySignature(array $notification): bool
    {
        $orderId = $notification['order_id'] ?? '';
        $statusCode = $notification['status_code'] ?? '';
        $grossAmount = $notification['gross_amount'] ?? '';
        $serverKey = config('midtrans.server_key');

        $signature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        return $signature === ($notification['signature_key'] ?? '');
    }

    /**
     * Handle notification from Midtrans.
     */
    public function handleNotification(array $notification): ?Savings
    {
        $orderId = $notification['order_id'] ?? null;
        
        if (!$orderId) {
            return null;
        }

        $savings = Savings::where('midtrans_order_id', $orderId)->first();

        if (!$savings) {
            return null;
        }

        $transactionStatus = $notification['transaction_status'] ?? '';
        $fraudStatus = $notification['fraud_status'] ?? '';
        $transactionId = $notification['transaction_id'] ?? null;

        // Update payment details
        $savings->payment_details = array_merge(
            $savings->payment_details ?? [],
            ['notification' => $notification]
        );

        switch ($transactionStatus) {
            case 'capture':
            case 'settlement':
                if ($fraudStatus !== 'challenge') {
                    $savings->markAsSuccess($transactionId, $notification);
                }
                break;

            case 'pending':
                $savings->update([
                    'payment_status' => Savings::STATUS_PENDING,
                    'payment_details' => $savings->payment_details,
                ]);
                break;

            case 'deny':
            case 'cancel':
                $savings->markAsFailed($notification);
                break;

            case 'expire':
                $savings->update([
                    'payment_status' => Savings::STATUS_EXPIRED,
                    'payment_details' => $savings->payment_details,
                ]);
                break;
        }

        $savings->save();

        return $savings;
    }

    /**
     * Check payment status.
     */
    public function checkStatus(string $orderId): array
    {
        try {
            $status = \Midtrans\Transaction::status($orderId);
            return [
                'success' => true,
                'status' => $status,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
