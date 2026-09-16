<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->longText('image_path')->nullable()->change();
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->longText('image_path')->nullable()->change();
        });
        Schema::table('settings', function (Blueprint $table) {
            $table->longText('logo_path')->nullable()->change();
            $table->longText('favicon_path')->nullable()->change();
            $table->longText('qr_image_path')->nullable()->change();
            $table->longText('hero_image_path')->nullable()->change();
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->longText('payment_proof')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('image_path')->nullable()->change();
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->text('image_path')->nullable()->change();
        });
        Schema::table('settings', function (Blueprint $table) {
            $table->string('logo_path', 255)->nullable()->change();
            $table->string('favicon_path', 255)->nullable()->change();
            $table->string('qr_image_path', 255)->nullable()->change();
            $table->string('hero_image_path', 255)->nullable()->change();
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_proof', 255)->nullable()->change();
        });
    }
};
