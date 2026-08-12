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
        Schema::create('product_addon', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id', 'product_addon_product_id_fk')->references('id')->on('products')->onDelete('cascade');
            $table->unsignedBigInteger('addon_id');
            $table->foreign('addon_id', 'product_addon_addon_id_fk')->references('id')->on('addons')->onDelete('cascade');
            $table->primary(['product_id', 'addon_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_addon');
    }
};
