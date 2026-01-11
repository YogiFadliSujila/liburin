<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DestinationController extends Controller
{
    /**
     * Display a listing of destinations for a trip.
     */
    public function index(Trip $trip): Response
    {
        Gate::authorize('view', $trip);

        $destinations = $trip->destinations()
            ->orderBy('visit_date')
            ->orderBy('order')
            ->get()
            ->groupBy(fn($dest) => $dest->visit_date->format('Y-m-d'));

        return Inertia::render('Trips/Destinations/Index', [
            'trip' => [
                'id' => $trip->id,
                'name' => $trip->name,
                'start_date' => $trip->start_date->format('Y-m-d'),
                'end_date' => $trip->end_date->format('Y-m-d'),
            ],
            'destinations' => $destinations,
            'isAdmin' => $trip->isAdmin(Auth::user()),
        ]);
    }

    /**
     * Store a newly created destination.
     */
    public function store(Request $request, Trip $trip): RedirectResponse
    {
        Gate::authorize('update', $trip);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'location' => 'nullable|string|max:255',
            'location_url' => 'nullable|url|max:500',
            'visit_date' => 'required|date|after_or_equal:' . $trip->start_date->format('Y-m-d') . '|before_or_equal:' . $trip->end_date->format('Y-m-d'),
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'estimated_cost' => 'nullable|numeric|min:0',
            'category' => 'sometimes|string|in:attraction,food,transport,accommodation,other',
        ]);

        // Get the max order for this date
        $maxOrder = $trip->destinations()
            ->where('visit_date', $validated['visit_date'])
            ->max('order') ?? -1;

        $validated['trip_id'] = $trip->id;
        $validated['order'] = $maxOrder + 1;

        Destination::create($validated);

        return back()->with('success', 'Destinasi berhasil ditambahkan!');
    }

    /**
     * Update the specified destination.
     */
    public function update(Request $request, Trip $trip, Destination $destination): RedirectResponse
    {
        Gate::authorize('update', $trip);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'location' => 'nullable|string|max:255',
            'location_url' => 'nullable|url|max:500',
            'visit_date' => 'required|date|after_or_equal:' . $trip->start_date->format('Y-m-d') . '|before_or_equal:' . $trip->end_date->format('Y-m-d'),
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'estimated_cost' => 'nullable|numeric|min:0',
            'category' => 'sometimes|string|in:attraction,food,transport,accommodation,other',
        ]);

        $destination->update($validated);

        return back()->with('success', 'Destinasi berhasil diperbarui!');
    }

    /**
     * Remove the specified destination.
     */
    public function destroy(Trip $trip, Destination $destination): RedirectResponse
    {
        Gate::authorize('update', $trip);

        $destination->delete();

        return back()->with('success', 'Destinasi berhasil dihapus!');
    }

    /**
     * Reorder destinations (for drag-and-drop).
     */
    public function reorder(Request $request, Trip $trip): RedirectResponse
    {
        Gate::authorize('update', $trip);

        $validated = $request->validate([
            'destinations' => 'required|array',
            'destinations.*.id' => 'required|exists:destinations,id',
            'destinations.*.order' => 'required|integer|min:0',
            'destinations.*.visit_date' => 'required|date',
        ]);

        foreach ($validated['destinations'] as $item) {
            Destination::where('id', $item['id'])
                ->where('trip_id', $trip->id)
                ->update([
                    'order' => $item['order'],
                    'visit_date' => $item['visit_date'],
                ]);
        }

        return back()->with('success', 'Urutan destinasi berhasil diperbarui!');
    }
}
