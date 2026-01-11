<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\TripMember;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TripController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Display a listing of the user's trips.
     */
    public function index(): Response
    {
        $trips = Auth::user()
            ->trips()
            ->wherePivot('status', 'active')
            ->withCount(['activeMembers', 'destinations'])
            ->with('creator:id,name')
            ->latest()
            ->get()
            ->map(fn($trip) => [
                'id' => $trip->id,
                'name' => $trip->name,
                'destination' => $trip->destination,
                'start_date' => $trip->start_date->format('Y-m-d'),
                'end_date' => $trip->end_date->format('Y-m-d'),
                'status' => $trip->status,
                'cover_image' => $trip->cover_image,
                'target_amount' => $trip->target_amount,
                'total_savings' => $trip->total_savings,
                'savings_progress' => $trip->savings_progress,
                'members_count' => $trip->active_members_count,
                'destinations_count' => $trip->destinations_count,
                'is_admin' => $trip->pivot->role === 'admin',
                'creator' => $trip->creator,
            ]);

        return Inertia::render('Trips/Index', [
            'trips' => $trips,
        ]);
    }

    /**
     * Show the form for creating a new trip.
     */
    public function create(): Response
    {
        return Inertia::render('Trips/Create');
    }

    /**
     * Store a newly created trip.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'destination' => 'required|string|max:255',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'target_amount' => 'required|numeric|min:0',
            'cover_image' => 'nullable|image|max:2048',
        ]);

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')
                ->store('trips/covers', 'public');
        }

        $validated['created_by'] = Auth::id();

        $trip = Trip::create($validated);

        // Add creator as admin member
        TripMember::create([
            'trip_id' => $trip->id,
            'user_id' => Auth::id(),
            'role' => TripMember::ROLE_ADMIN,
            'status' => TripMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        // Log audit
        $this->auditService->logTripCreated($trip);

        return redirect()
            ->route('trips.show', $trip)
            ->with('success', 'Trip berhasil dibuat!');
    }

    /**
     * Display the specified trip.
     */
    public function show(Trip $trip): Response
    {
        Gate::authorize('view', $trip);

        $trip->load([
            'creator:id,name,email',
            'activeMembers:id,name,email',
            'destinations' => fn($q) => $q->orderBy('visit_date')->orderBy('order'),
        ]);

        $userMembership = $trip->tripMembers()
            ->where('user_id', Auth::id())
            ->first();

        return Inertia::render('Trips/Show', [
            'trip' => [
                'id' => $trip->id,
                'name' => $trip->name,
                'description' => $trip->description,
                'destination' => $trip->destination,
                'start_date' => $trip->start_date->format('Y-m-d'),
                'end_date' => $trip->end_date->format('Y-m-d'),
                'status' => $trip->status,
                'cover_image' => $trip->cover_image,
                'target_amount' => (float) $trip->target_amount,
                'total_savings' => $trip->total_savings,
                'savings_progress' => round($trip->savings_progress, 1),
                'total_expenses' => $trip->total_expenses,
                'remaining_balance' => $trip->remaining_balance,
                'creator' => $trip->creator,
                'created_at' => $trip->created_at->format('Y-m-d'),
            ],
            'members' => $trip->activeMembers->map(fn($member) => [
                'id' => $member->id,
                'trip_member_id' => $member->pivot->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->pivot->role,
                'joined_at' => $member->pivot->joined_at 
                    ? (is_string($member->pivot->joined_at) 
                        ? \Carbon\Carbon::parse($member->pivot->joined_at)->format('Y-m-d') 
                        : $member->pivot->joined_at->format('Y-m-d'))
                    : null,
            ]),
            'destinations' => $trip->destinations->map(fn($dest) => [
                'id' => $dest->id,
                'name' => $dest->name,
                'description' => $dest->description,
                'location' => $dest->location,
                'location_url' => $dest->location_url,
                'visit_date' => $dest->visit_date->format('Y-m-d'),
                'start_time' => $dest->start_time?->format('H:i'),
                'end_time' => $dest->end_time?->format('H:i'),
                'order' => $dest->order,
                'estimated_cost' => (float) $dest->estimated_cost,
                'category' => $dest->category,
            ]),
            'userRole' => $userMembership?->role ?? 'member',
            'isAdmin' => $trip->isAdmin(Auth::user()),
        ]);
    }

    /**
     * Show the form for editing the trip.
     */
    public function edit(Trip $trip): Response
    {
        Gate::authorize('update', $trip);

        return Inertia::render('Trips/Edit', [
            'trip' => [
                'id' => $trip->id,
                'name' => $trip->name,
                'description' => $trip->description,
                'destination' => $trip->destination,
                'start_date' => $trip->start_date->format('Y-m-d'),
                'end_date' => $trip->end_date->format('Y-m-d'),
                'target_amount' => (float) $trip->target_amount,
                'status' => $trip->status,
                'cover_image' => $trip->cover_image,
            ],
        ]);
    }

    /**
     * Update the specified trip.
     */
    public function update(Request $request, Trip $trip): RedirectResponse
    {
        Gate::authorize('update', $trip);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'destination' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'target_amount' => 'required|numeric|min:0',
            'status' => 'sometimes|string|in:planning,saving,ongoing,completed,cancelled',
            'cover_image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')
                ->store('trips/covers', 'public');
        }

        $trip->update($validated);

        return redirect()
            ->route('trips.show', $trip)
            ->with('success', 'Trip berhasil diperbarui!');
    }

    /**
     * Remove the specified trip.
     */
    public function destroy(Trip $trip): RedirectResponse
    {
        Gate::authorize('delete', $trip);

        $trip->delete();

        return redirect()
            ->route('trips.index')
            ->with('success', 'Trip berhasil dihapus!');
    }
}
