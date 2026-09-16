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
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE categories MODIFY image_path LONGTEXT NULL');
            DB::statement('ALTER TABLE products MODIFY image_path LONGTEXT NULL');
            DB::statement('ALTER TABLE addons MODIFY image_path LONGTEXT NULL');
            DB::statement('ALTER TABLE orders MODIFY payment_proof LONGTEXT NULL');
        } else {
            Schema::table('categories', function (Blueprint $table) {
                $table->longText('image_path')->nullable()->change();
            });
            Schema::table('products', function (Blueprint $table) {
                $table->longText('image_path')->nullable()->change();
            });
            Schema::table('addons', function (Blueprint $table) {
                $table->longText('image_path')->nullable()->change();
            });
            Schema::table('orders', function (Blueprint $table) {
                $table->longText('payment_proof')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE categories MODIFY image_path TEXT NULL');
            DB::statement('ALTER TABLE products MODIFY image_path TEXT NULL');
            DB::statement('ALTER TABLE addons MODIFY image_path VARCHAR(255) NULL');
            DB::statement('ALTER TABLE orders MODIFY payment_proof VARCHAR(255) NULL');
        } else {
            Schema::table('categories', function (Blueprint $table) {
                $table->text('image_path')->nullable()->change();
            });
            Schema::table('products', function (Blueprint $table) {
                $table->text('image_path')->nullable()->change();
            });
            Schema::table('addons', function (Blueprint $table) {
                $table->string('image_path', 255)->nullable()->change();
            });
            Schema::table('orders', function (Blueprint $table) {
                $table->string('payment_proof', 255)->nullable()->change();
            });
        }
    }
};
