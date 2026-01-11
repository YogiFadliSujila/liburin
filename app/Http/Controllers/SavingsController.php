<?php

namespace App\Http\Controllers;

use App\Models\Savings;
use App\Models\Trip;
use App\Services\AuditService;
use App\Services\MidtransService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SavingsController extends Controller
{
    public function __construct(
        protected MidtransService $midtransService,
        protected AuditService $auditService
    ) {}

    /**
     * Display savings history for a trip.
     */
    public function index(Trip $trip): Response
    {
        Gate::authorize('view', $trip);

        $savings = $trip->savings()
            ->with('user:id,name,email')
            ->latest()
            ->get()
            ->map(fn($saving) => [
                'id' => $saving->id,
                'user' => $saving->user,
                'amount' => (float) $saving->amount,
                'payment_method' => $saving->payment_method,
                'payment_status' => $saving->payment_status,
                'paid_at' => $saving->paid_at?->format('Y-m-d H:i'),
                'created_at' => $saving->created_at->format('Y-m-d H:i'),
            ]);

        // Calculate user contributions
        $userContributions = $trip->savings()
            ->where('payment_status', Savings::STATUS_SUCCESS)
            ->selectRaw('user_id, SUM(amount) as total')
            ->groupBy('user_id')
            ->with('user:id,name')
            ->get()
            ->map(fn($item) => [
                'user' => $item->user,
                'total' => (float) $item->total,
            ]);

        return Inertia::render('Trips/Savings/Index', [
            'trip' => [
                'id' => $trip->id,
                'name' => $trip->name,
                'target_amount' => (float) $trip->target_amount,
                'total_savings' => $trip->total_savings,
                'savings_progress' => round($trip->savings_progress, 1),
            ],
            'savings' => $savings,
            'userContributions' => $userContributions,
            'isAdmin' => $trip->isAdmin(Auth::user()),
            'clientKey' => config('midtrans.client_key'),
        ]);
    }

    /**
     * Show payment form.
     */
    public function create(Trip $trip): Response
    {
        Gate::authorize('manageFinances', $trip);

        $isAdmin = $trip->isAdmin(Auth::user());

        return Inertia::render('Trips/Savings/Create', [
            'trip' => [
                'id' => $trip->id,
                'name' => $trip->name,
                'target_amount' => (float) $trip->target_amount,
                'total_savings' => $trip->total_savings,
                'remaining' => max(0, (float) $trip->target_amount - $trip->total_savings),
            ],
            'members' => $isAdmin ? $trip->members()->get()->map(fn($m) => ['id' => $m->id, 'name' => $m->name]) : [],
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Initiate a new payment.
     */
    public function store(Request $request, Trip $trip): RedirectResponse
    {
        Gate::authorize('manageFinances', $trip);

        $isAdmin = $trip->isAdmin(Auth::user());

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1000',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:255',
            'user_id' => $isAdmin ? 'required|exists:users,id' : 'nullable', // Admin can select user
        ]);

        // Determine user_id: Admin can select, Member is self
        $targetUserId = $isAdmin ? $validated['user_id'] : Auth::id();

        // Determine status: Admin -> Success, Member -> Pending
        $status = $isAdmin ? Savings::STATUS_SUCCESS : Savings::STATUS_PENDING;

        $savings = Savings::create([
            'trip_id' => $trip->id,
            'user_id' => $targetUserId,
            'amount' => $validated['amount'],
            'payment_method' => 'manual', // Generic manual method
            'payment_status' => $status,
            'notes' => $validated['notes'] ?? null,
            'paid_at' => $isAdmin ? $validated['payment_date'] : null,
        ]);

        // If admin created (success), log it
        if ($isAdmin) {
             $this->auditService->logPaymentReceived($trip, $savings);
        }

        return redirect()
            ->route('trips.savings.index', $trip)
            ->with('success', $isAdmin ? 'Pembayaran berhasil dicatat.' : 'Laporan pembayaran berhasil dikirim. Menunggu konfirmasi admin.');
    }

    /**
     * Approve a pending payment.
     */
    public function approve(Trip $trip, Savings $savings): RedirectResponse
    {
        // Only Admin/Owner can approve
        if (!$trip->isAdmin(Auth::user())) {
            abort(403);
        }

        if ($savings->trip_id !== $trip->id) {
            abort(404);
        }

        $savings->update([
            'payment_status' => Savings::STATUS_SUCCESS,
            'paid_at' => now(),
        ]);

        $this->auditService->logPaymentReceived($trip, $savings);

        return back()->with('success', 'Pembayaran berhasil dikonfirmasi.');
    }

    /**
     * Show payment status/details.
     */
    public function show(Trip $trip, Savings $savings): Response
    {
        Gate::authorize('view', $trip);

        // Ensure savings belongs to trip
        if ($savings->trip_id !== $trip->id) {
            abort(404);
        }

        $paymentDetails = $savings->payment_details ?? [];

        return Inertia::render('Trips/Savings/Show', [
            'trip' => [
                'id' => $trip->id,
                'name' => $trip->name,
            ],
            'savings' => [
                'id' => $savings->id,
                'user' => [
                    'id' => $savings->user->id,
                    'name' => $savings->user->name,
                ],
                'amount' => (float) $savings->amount,
                'payment_method' => $savings->payment_method,
                'payment_status' => $savings->payment_status,
                'midtrans_order_id' => $savings->midtrans_order_id,
                'expires_at' => $savings->expires_at?->format('Y-m-d H:i:s'),
                'paid_at' => $savings->paid_at?->format('Y-m-d H:i:s'),
                'notes' => $savings->notes,
                'created_at' => $savings->created_at->format('Y-m-d H:i'),
                // Payment specific data
                'snap_token' => $paymentDetails['snap_token'] ?? null,
                'qr_string' => $paymentDetails['actions'][0]['url'] ?? null,
                'va_number' => $paymentDetails['va_numbers'][0]['va_number'] ?? $paymentDetails['permata_va_number'] ?? null,
                'bank' => $paymentDetails['va_numbers'][0]['bank'] ?? null,
            ],
            'clientKey' => config('midtrans.client_key'),
            'isProduction' => config('midtrans.is_production'),
            'isOwner' => $savings->user_id === Auth::id(),
            'isAdmin' => $trip->isAdmin(Auth::user()),
        ]);
    }

    /**
     * Check payment status (AJAX).
     */
    public function checkStatus(Trip $trip, Savings $savings)
    {
        Gate::authorize('view', $trip);

        if ($savings->trip_id !== $trip->id) {
            abort(404);
        }

        // If already successful or failed, return current status
        if (in_array($savings->payment_status, [Savings::STATUS_SUCCESS, Savings::STATUS_FAILED, Savings::STATUS_EXPIRED])) {
            return response()->json([
                'status' => $savings->payment_status,
                'paid_at' => $savings->paid_at?->format('Y-m-d H:i:s'),
            ]);
        }

        // Check with Midtrans
        if ($savings->midtrans_order_id) {
            $result = $this->midtransService->checkStatus($savings->midtrans_order_id);
            
            if ($result['success'] && isset($result['status'])) {
                $transactionStatus = $result['status']->transaction_status ?? '';
                
                if (in_array($transactionStatus, ['capture', 'settlement'])) {
                    $savings->markAsSuccess(
                        $result['status']->transaction_id ?? null,
                        (array) $result['status']
                    );
                    
                    // Log audit
                    $this->auditService->logPaymentReceived($trip, $savings);
                } elseif ($transactionStatus === 'expire') {
                    $savings->update(['payment_status' => Savings::STATUS_EXPIRED]);
                } elseif (in_array($transactionStatus, ['deny', 'cancel'])) {
                    $savings->markAsFailed((array) $result['status']);
                }
            }
        }

        return response()->json([
            'status' => $savings->payment_status,
            'paid_at' => $savings->paid_at?->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Cancel pending payment.
     */
    public function destroy(Trip $trip, Savings $savings): RedirectResponse
    {
        Gate::authorize('manageFinances', $trip);

        if ($savings->trip_id !== $trip->id) {
            abort(404);
        }

        // Only allow canceling own pending payments
        if ($savings->user_id !== Auth::id() || $savings->payment_status !== Savings::STATUS_PENDING) {
            return back()->withErrors(['payment' => 'Tidak dapat membatalkan pembayaran ini.']);
        }

        $savings->update(['payment_status' => Savings::STATUS_FAILED]);

        return redirect()
            ->route('trips.savings.index', $trip)
            ->with('success', 'Pembayaran dibatalkan.');
    }
}
