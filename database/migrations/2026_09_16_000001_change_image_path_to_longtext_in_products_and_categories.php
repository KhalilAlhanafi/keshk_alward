<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * تحويل عمود image_path إلى longText لدعم تخزين الصور كـ Base64
     * في بيئات الاستضافة ذات الـ Ephemeral Filesystem (Wasmer, Render, Railway...)
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE products MODIFY image_path LONGTEXT');
        // categories might have already been modified if the migration ran partially,
        // but it's safe to run it again or just let the second migration handle it.
        // Actually, we'll just run it here too.
        DB::statement('ALTER TABLE categories MODIFY image_path LONGTEXT');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE products MODIFY image_path TEXT');
        DB::statement('ALTER TABLE categories MODIFY image_path TEXT');
    }
};
