<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE categories MODIFY image_path LONGTEXT');

        DB::statement('ALTER TABLE settings MODIFY logo_path LONGTEXT');
        DB::statement('ALTER TABLE settings MODIFY favicon_path LONGTEXT');
        DB::statement('ALTER TABLE settings MODIFY qr_image_path LONGTEXT');
        DB::statement('ALTER TABLE settings MODIFY hero_image_path LONGTEXT');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE categories MODIFY image_path VARCHAR(255)');

        DB::statement('ALTER TABLE settings MODIFY logo_path VARCHAR(255)');
        DB::statement('ALTER TABLE settings MODIFY favicon_path VARCHAR(255)');
        DB::statement('ALTER TABLE settings MODIFY qr_image_path VARCHAR(255)');
        DB::statement('ALTER TABLE settings MODIFY hero_image_path VARCHAR(255)');
    }
};
