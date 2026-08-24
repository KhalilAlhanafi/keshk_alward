<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class SettingController extends Controller
{
    /**
     * Get all settings.
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse|\Illuminate\View\View
    {
        $settings = Setting::all()->pluck('value', 'key');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($settings);
        }

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update settings.
     */
    public function update(Request $request): JsonResponse
    {
        // Check policy - only admins can update settings
        Gate::authorize('update', Setting::class);

        // Support setting file uploads (like hero banner image) or simple JSON parameters
        $rules = [
            'hero_banner_text' => ['nullable', 'string', 'max:500'],
            'hero_banner_image' => ['nullable', 'image', 'max:4096'],
            'promo_banner_text' => ['nullable', 'string', 'max:500'],
            'sham_cash_wallet_code' => ['nullable', 'string', 'max:100'],
            'payment_cod_enabled' => ['nullable', 'boolean'],
            'payment_sham_cash_enabled' => ['nullable', 'boolean'],
        ];

        $validated = $request->validate($rules);

        foreach ($validated as $key => $value) {
            if ($request->hasFile($key)) {
                // Delete old image if any
                $oldPath = Setting::get($key);
                if ($oldPath) {
                    Storage::disk('public')->delete($oldPath);
                }
                $value = $request->file($key)->store('settings', 'public');
            }

            // Keep booleans as actual booleans/integers
            if (in_array($key, ['payment_cod_enabled', 'payment_sham_cash_enabled'])) {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }

            Setting::set($key, $value);
        }

        return response()->json([
            'message' => 'تم حفظ الإعدادات بنجاح.',
            'settings' => Setting::all()->pluck('value', 'key'),
        ]);
    }
}
