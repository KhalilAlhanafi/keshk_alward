<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE products MODIFY image_path LONGTEXT');
        DB::statement('ALTER TABLE categories MODIFY image_path LONGTEXT');
        DB::statement('ALTER TABLE settings MODIFY logo_path LONGTEXT');
        DB::statement('ALTER TABLE settings MODIFY favicon_path LONGTEXT');
        DB::statement('ALTER TABLE settings MODIFY qr_image_path LONGTEXT');
        DB::statement('ALTER TABLE settings MODIFY hero_image_path LONGTEXT');
        DB::statement('ALTER TABLE orders MODIFY payment_proof LONGTEXT');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE products MODIFY image_path TEXT');
        DB::statement('ALTER TABLE categories MODIFY image_path TEXT');
        DB::statement('ALTER TABLE settings MODIFY logo_path VARCHAR(255)');
        DB::statement('ALTER TABLE settings MODIFY favicon_path VARCHAR(255)');
        DB::statement('ALTER TABLE settings MODIFY qr_image_path VARCHAR(255)');
        DB::statement('ALTER TABLE settings MODIFY hero_image_path VARCHAR(255)');
        DB::statement('ALTER TABLE orders MODIFY payment_proof VARCHAR(255)');
    }
};
