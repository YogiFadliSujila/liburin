<?php

namespace App\Http\Controllers;

use App\Events\WithdrawalVoteUpdated;
use App\Models\Trip;
use App\Models\Withdrawal;
use App\Models\WithdrawalVote;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WithdrawalController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Display withdrawals for a trip.
     */
    public function index(Trip $trip): Response
    {
        Gate::authorize('view', $trip);

        $withdrawals = $trip->withdrawals()
            ->with(['requester:id,name', 'votes.user:id,name'])
            ->latest()
            ->get()
            ->map(fn($withdrawal) => [
                'id' => $withdrawal->id,
                'amount' => (float) $withdrawal->amount,
                'reason' => $withdrawal->reason,
                'description' => $withdrawal->description,
                'status' => $withdrawal->status,
                'votes_required' => $withdrawal->votes_required,
                'votes_approve' => $withdrawal->votes_approve,
                'votes_reject' => $withdrawal->votes_reject,
                'approval_progress' => $withdrawal->approval_progress,
                'voting_deadline' => $withdrawal->voting_deadline->format('Y-m-d H:i'),
                'is_voting_open' => $withdrawal->isVotingOpen(),
                'requester' => $withdrawal->requester,
                'created_at' => $withdrawal->created_at->format('Y-m-d H:i'),
                'user_vote' => $withdrawal->getUserVote(Auth::user())?->approved,
            ]);

        return Inertia::render('Trips/Withdrawals/Index', [
            'trip' => [
                'id' => $trip->id,
                'name' => $trip->name,
                'remaining_balance' => $trip->remaining_balance,
            ],
            'withdrawals' => $withdrawals,
            'isAdmin' => $trip->isAdmin(Auth::user()),
            'activeMembersCount' => $trip->activeMembers()->count(),
        ]);
    }

    /**
     * Store a new withdrawal request.
     */
    public function store(Request $request, Trip $trip): RedirectResponse
    {
        Gate::authorize('requestWithdrawal', $trip);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:10000|max:' . $trip->remaining_balance,
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'voting_days' => 'required|integer|min:1|max:7',
        ]);

        $activeMembersCount = $trip->activeMembers()->count();
        $votesRequired = (int) ceil($activeMembersCount / 2); // Majority

        $withdrawal = Withdrawal::create([
            'trip_id' => $trip->id,
            'requested_by' => Auth::id(),
            'amount' => $validated['amount'],
            'reason' => $validated['reason'],
            'description' => $validated['description'] ?? null,
            'votes_required' => $votesRequired,
            'voting_deadline' => now()->addDays($validated['voting_days']),
        ]);

        // Auto-approve by requester
        WithdrawalVote::create([
            'withdrawal_id' => $withdrawal->id,
            'user_id' => Auth::id(),
            'approved' => true,
            'comment' => 'Requested by me',
        ]);

        $withdrawal->increment('votes_approve');
        $withdrawal->processVoteResult();

        $this->auditService->logWithdrawalRequested($trip, $withdrawal);
        
        // Broadcast real-time update
        broadcast(new WithdrawalVoteUpdated($withdrawal->fresh()))->toOthers();

        return back()->with('success', 'Permintaan penarikan berhasil dibuat. Menunggu persetujuan anggota.');
    }

    /**
     * Vote on a withdrawal request.
     */
    public function vote(Request $request, Trip $trip, Withdrawal $withdrawal): RedirectResponse
    {
        Gate::authorize('view', $trip);

        if ($withdrawal->trip_id !== $trip->id) {
            abort(404);
        }

        // Check if voting is still open
        if (!$withdrawal->isVotingOpen()) {
            return back()->withErrors(['vote' => 'Voting sudah ditutup.']);
        }

        // Check if already voted
        if ($withdrawal->hasVoted(Auth::user())) {
            return back()->withErrors(['vote' => 'Anda sudah memberikan suara.']);
        }

        $validated = $request->validate([
            'approved' => 'required|boolean',
            'comment' => 'nullable|string|max:500',
        ]);

        WithdrawalVote::create([
            'withdrawal_id' => $withdrawal->id,
            'user_id' => Auth::id(),
            'approved' => $validated['approved'],
            'comment' => $validated['comment'] ?? null,
        ]);

        if ($validated['approved']) {
            $withdrawal->increment('votes_approve');
        } else {
            $withdrawal->increment('votes_reject');
        }

        // Check if result is determined
        $withdrawal->processVoteResult();

        // Log audit
        $this->auditService->log(
            $trip,
            'vote_cast',
            $withdrawal,
            null,
            [
                'voter' => Auth::user()->name,
                'approved' => $validated['approved'],
            ]
        );
        
        // Broadcast real-time update
        broadcast(new WithdrawalVoteUpdated($withdrawal->fresh()))->toOthers();

        return back()->with('success', 'Suara berhasil diberikan.');
    }

    /**
     * Cancel a withdrawal request (only by requester, only if pending).
     */
    public function destroy(Trip $trip, Withdrawal $withdrawal): RedirectResponse
    {
        Gate::authorize('requestWithdrawal', $trip);

        if ($withdrawal->trip_id !== $trip->id) {
            abort(404);
        }

        if ($withdrawal->requested_by !== Auth::id()) {
            return back()->withErrors(['withdrawal' => 'Anda tidak dapat membatalkan permintaan ini.']);
        }

        if ($withdrawal->status !== Withdrawal::STATUS_PENDING) {
            return back()->withErrors(['withdrawal' => 'Permintaan ini tidak dapat dibatalkan.']);
        }

        $withdrawal->update(['status' => Withdrawal::STATUS_CANCELLED]);

        return back()->with('success', 'Permintaan penarikan dibatalkan.');
    }

    /**
     * Mark withdrawal as completed (admin only, after funds transferred).
     */
    public function complete(Trip $trip, Withdrawal $withdrawal): RedirectResponse
    {
        Gate::authorize('requestWithdrawal', $trip);

        if ($withdrawal->trip_id !== $trip->id) {
            abort(404);
        }

        if ($withdrawal->status !== Withdrawal::STATUS_APPROVED) {
            return back()->withErrors(['withdrawal' => 'Permintaan belum disetujui.']);
        }

        $withdrawal->update([
            'status' => Withdrawal::STATUS_COMPLETED,
            'processed_at' => now(),
        ]);

        $this->auditService->log(
            $trip,
            'withdrawal_completed',
            $withdrawal,
            null,
            ['amount' => $withdrawal->amount]
        );

        return back()->with('success', 'Penarikan telah diselesaikan.');
    }
}
