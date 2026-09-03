<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'store_manager']) || 
               ($user->hasRole('admin') || $user->hasRole('store_manager'));
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Order $order): bool
    {
        // Admins and store managers can view any order
        if (in_array($user->role, ['admin', 'store_manager']) || 
            ($user->hasRole('admin') || $user->hasRole('store_manager'))) {
            return true;
        }

        // Customers can only view their own orders
        return $user->id === $order->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // All authenticated users can create orders
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Order $order): bool
    {
        return in_array($user->role, ['admin', 'store_manager']) || 
               ($user->hasRole('admin') || $user->hasRole('store_manager'));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Order $order): bool
    {
        // Only admins can delete orders
        return $user->role === 'admin' || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can verify payments.
     */
    public function verifyPayment(User $user, Order $order): bool
    {
        // Admins and store managers can verify payments
        return in_array($user->role, ['admin', 'store_manager']) || 
               ($user->hasRole('admin') || $user->hasRole('store_manager'));
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Order $order): bool
    {
        return $user->role === 'admin' || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Order $order): bool
    {
        return $user->role === 'admin' || $user->hasRole('admin');
    }
}
