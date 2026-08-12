<?php

namespace App\Policies;

use App\Models\User;

class SettingPolicy
{
    /**
     * Determine whether the user can view settings.
     */
    public function view(User $user): bool
    {
        return $user->hasRole(['admin', 'store_manager']);
    }

    /**
     * Determine whether the user can update settings.
     */
    public function update(User $user): bool
    {
        // Only admins can update settings, especially payment settings
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can manage payment settings.
     */
    public function managePaymentSettings(User $user): bool
    {
        // Only admins can manage payment settings
        return $user->hasRole('admin');
    }
}
