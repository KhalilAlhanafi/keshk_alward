<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    /**
     * Get all settings.
     */
    public function index(Request $request): JsonResponse|\Illuminate\View\View
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
    public function update(Request $request): JsonResponse|RedirectResponse
    {
        // Check policy - only admins can update settings
        Gate::authorize('update', Setting::class);

        $rules = [
            'home_hero_title' => ['nullable', 'string', 'max:500'],
            'home_hero_subtitle' => ['nullable', 'string', 'max:1000'],
            'home_hero_image' => ['nullable', 'image', 'max:25600'],
            'promo_banner_badge' => ['nullable', 'string', 'max:255'],
            'promo_banner_title' => ['nullable', 'string', 'max:500'],
            'promo_banner_text' => ['nullable', 'string', 'max:1000'],
            'whatsapp_number' => ['nullable', 'string', 'max:100'],
            'facebook_url' => ['nullable', 'string', 'max:500'],
            'instagram_url' => ['nullable', 'string', 'max:500'],
            'sham_cash_wallet_code' => ['nullable', 'string', 'max:100'],
            'sham_cash_qr_image' => ['nullable', 'image', 'max:25600'],
            'payment_cod_enabled' => ['nullable'],
            'payment_sham_cash_enabled' => ['nullable'],
        ];

        $validated = $request->validate($rules);

        foreach ($validated as $key => $value) {
            if ($request->hasFile($key)) {
                $file = $request->file($key);
                $oldVal = Setting::get($key);
                if ($oldVal && !str_starts_with($oldVal, 'data:') && !str_starts_with($oldVal, 'http')) {
                    Storage::disk('public')->delete($oldVal);
                }
                $value = $file->store('settings', 'public');
            }

            // Keep booleans as actual booleans
            if (in_array($key, ['payment_cod_enabled', 'payment_sham_cash_enabled'])) {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }

            Setting::set($key, $value);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'تم حفظ الإعدادات بنجاح.',
                'settings' => Setting::all()->pluck('value', 'key'),
            ]);
        }

        return redirect()->route('admin.settings.index')->with('success', 'تم حفظ الإعدادات بنجاح.');
    }
}
