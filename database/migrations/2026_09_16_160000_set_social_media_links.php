<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Setting::set('facebook_url', 'https://www.facebook.com/share/19CMhfvDnZ/');
            Setting::set('instagram_url', 'https://www.instagram.com/keshkalward.kshk?utm_source=qr&stkn=am5rYTZrdmw5dmU=');
        } catch (\Throwable $e) {
            // Ignore if tables aren't migrated yet
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
