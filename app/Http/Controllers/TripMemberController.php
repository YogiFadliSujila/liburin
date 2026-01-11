<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\TripMember;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TripMemberController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Display the members of a trip.
     */
    public function index(Trip $trip): Response
    {
        Gate::authorize('view', $trip);

        $members = $trip->tripMembers()
            ->with('user:id,name,email')
            ->get()
            ->map(fn($member) => [
                'id' => $member->id,
                'user' => $member->user,
                'role' => $member->role,
                'status' => $member->status,
                'joined_at' => $member->joined_at?->format('Y-m-d H:i'),
            ]);

        return Inertia::render('Trips/Members/Index', [
            'trip' => [
                'id' => $trip->id,
                'name' => $trip->name,
            ],
            'members' => $members,
            'isAdmin' => $trip->isAdmin(Auth::user()),
        ]);
    }

    /**
     * Invite a new member to the trip.
     */
    public function store(Request $request, Trip $trip): RedirectResponse
    {
        Gate::authorize('manageMembers', $trip);

        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'role' => 'sometimes|string|in:admin,member',
        ]);

        $user = User::where('email', $validated['email'])->first();

        // Check if already a member
        $existingMember = TripMember::where('trip_id', $trip->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingMember) {
            if (in_array($existingMember->status, [TripMember::STATUS_ACTIVE, TripMember::STATUS_PENDING])) {
                return back()->withErrors(['email' => 'User sudah menjadi anggota atau memiliki undangan pending.']);
            }
            
            // Re-invite member who left
            $existingMember->update([
                'status' => TripMember::STATUS_PENDING,
                'role' => $validated['role'] ?? TripMember::ROLE_MEMBER,
                'joined_at' => null, // Reset joined_at
            ]);
            
            return back()->with('success', 'Undangan berhasil dikirim ulang!');
        }

        $member = TripMember::create([
            'trip_id' => $trip->id,
            'user_id' => $user->id,
            'role' => $validated['role'] ?? TripMember::ROLE_MEMBER,
            'status' => TripMember::STATUS_PENDING,
        ]);

        // TODO: Send invitation notification to user

        return back()->with('success', 'Undangan berhasil dikirim!');
    }

    /**
     * Accept the invitation to join the trip.
     */
    public function accept(Trip $trip): RedirectResponse
    {
        $member = $trip->tripMembers()
            ->where('user_id', Auth::id())
            ->where('status', TripMember::STATUS_PENDING)
            ->firstOrFail();

        $member->update([
            'status' => TripMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        $this->auditService->logMemberJoined($trip, $member);

        return redirect()
            ->route('trips.show', $trip)
            ->with('success', 'Anda berhasil bergabung ke trip!');
    }

    /**
     * Decline the invitation.
     */
    public function decline(Trip $trip): RedirectResponse
    {
        $member = $trip->tripMembers()
            ->where('user_id', Auth::id())
            ->where('status', TripMember::STATUS_PENDING)
            ->firstOrFail();

        $member->delete();

        return redirect()
            ->route('trips.index')
            ->with('success', 'Undangan ditolak.');
    }

    /**
     * Update a member's role.
     */
    public function update(Request $request, Trip $trip, TripMember $member): RedirectResponse
    {
        Gate::authorize('manageMembers', $trip);

        $validated = $request->validate([
            'role' => 'required|string|in:admin,member',
        ]);

        // Prevent removing the last admin
        if ($member->role === TripMember::ROLE_ADMIN && $validated['role'] === TripMember::ROLE_MEMBER) {
            $adminCount = $trip->tripMembers()
                ->where('role', TripMember::ROLE_ADMIN)
                ->where('status', TripMember::STATUS_ACTIVE)
                ->count();

            if ($adminCount <= 1) {
                return back()->withErrors(['role' => 'Trip harus memiliki minimal 1 admin.']);
            }
        }

        $member->update($validated);

        return back()->with('success', 'Role anggota berhasil diperbarui!');
    }

    /**
     * Remove a member from the trip.
     */
    public function destroy(Trip $trip, TripMember $member): RedirectResponse
    {
        Gate::authorize('manageMembers', $trip);

        // Prevent removing the last admin
        if ($member->role === TripMember::ROLE_ADMIN) {
            $adminCount = $trip->tripMembers()
                ->where('role', TripMember::ROLE_ADMIN)
                ->where('status', TripMember::STATUS_ACTIVE)
                ->count();

            if ($adminCount <= 1) {
                return back()->withErrors(['member' => 'Tidak dapat menghapus admin terakhir.']);
            }
        }

        // Soft removal by changing status
        $member->update(['status' => TripMember::STATUS_LEFT]);

        return back()->with('success', 'Anggota berhasil dihapus dari trip.');
    }

    /**
     * Leave the trip voluntarily.
     */
    public function leave(Trip $trip): RedirectResponse
    {
        $member = $trip->tripMembers()
            ->where('user_id', Auth::id())
            ->where('status', TripMember::STATUS_ACTIVE)
            ->firstOrFail();

        // Prevent leaving if last admin
        if ($member->role === TripMember::ROLE_ADMIN) {
            $adminCount = $trip->tripMembers()
                ->where('role', TripMember::ROLE_ADMIN)
                ->where('status', TripMember::STATUS_ACTIVE)
                ->count();

            if ($adminCount <= 1) {
                return back()->withErrors(['member' => 'Admin terakhir tidak dapat meninggalkan trip. Transfer role admin terlebih dahulu.']);
            }
        }

        $member->update(['status' => TripMember::STATUS_LEFT]);

        return redirect()
            ->route('trips.index')
            ->with('success', 'Anda telah keluar dari trip.');
    }
}
