<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'store_manager']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Admins can view any user
        if ($user->hasRole('admin')) {
            return true;
        }

        // Store managers can view customers but not other managers
        if ($user->hasRole('store_manager')) {
            return !$model->hasRole(['admin', 'store_manager']);
        }

        // Users can view their own profile
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admins can create new users
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Admins can update any user
        if ($user->hasRole('admin')) {
            return true;
        }

        // Store managers can only update customers, not other managers
        if ($user->hasRole('store_manager')) {
            return !$model->hasRole(['admin', 'store_manager']);
        }

        // Users can update their own profile
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Only admins can delete users
        // Prevent deleting oneself
        return $user->hasRole('admin') && $user->id !== $model->id;
    }

    /**
     * Determine whether the user can manage roles.
     */
    public function manageRoles(User $user, User $model): bool
    {
        // Only admins can manage roles
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasRole('admin') && $user->id !== $model->id;
    }
}
