<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تحويل عمود image_path إلى longText لدعم تخزين الصور كـ Base64
     * في بيئات الاستضافة ذات الـ Ephemeral Filesystem (Wasmer, Render, Railway...)
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->longText('image_path')->nullable()->change();
        });
        // categories might have already been modified if the migration ran partially,
        // but it's safe to run it again or just let the second migration handle it.
        // Actually, we'll just run it here too.
        Schema::table('categories', function (Blueprint $table) {
            $table->longText('image_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('image_path')->nullable()->change();
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->text('image_path')->nullable()->change();
        });
    }
};
