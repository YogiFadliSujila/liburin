<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TripPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any trips.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the trip.
     */
    public function view(User $user, Trip $trip): bool
    {
        return $trip->hasMember($user);
    }

    /**
     * Determine whether the user can create trips.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the trip.
     */
    public function update(User $user, Trip $trip): bool
    {
        return $trip->isAdmin($user);
    }

    /**
     * Determine whether the user can delete the trip.
     */
    public function delete(User $user, Trip $trip): bool
    {
        return $trip->isAdmin($user);
    }

    /**
     * Determine whether the user can manage members.
     */
    public function manageMembers(User $user, Trip $trip): bool
    {
        return $trip->isAdmin($user);
    }

    /**
     * Determine whether the user can manage destinations (create, update, delete).
     */
    public function manageDestinations(User $user, Trip $trip): bool
    {
        return $trip->hasMember($user);
    }

    /**
     * Determine whether the user can manage finances.
     */
    public function manageFinances(User $user, Trip $trip): bool
    {
        return $trip->hasMember($user);
    }

    /**
     * Determine whether the user can request withdrawal.
     */
    public function requestWithdrawal(User $user, Trip $trip): bool
    {
        return $trip->isAdmin($user);
    }
}
