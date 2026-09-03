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
        Schema::table('cart_items', function (Blueprint $table) {
            $table->string('wrapping_color', 50)->nullable()->after('product_size_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('wrapping_color', 50)->nullable()->after('size_label_snapshot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn('wrapping_color');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('wrapping_color');
        });
    }
};
