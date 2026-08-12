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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id')->index();
            $table->foreign('cart_id', 'cart_items_cart_id_fk')->references('id')->on('carts')->onDelete('cascade');
            $table->unsignedBigInteger('product_id')->index();
            $table->foreign('product_id', 'cart_items_product_id_fk')->references('id')->on('products')->onDelete('cascade');
            $table->unsignedBigInteger('product_size_id')->index();
            $table->foreign('product_size_id', 'cart_items_product_size_id_fk')->references('id')->on('product_sizes')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
