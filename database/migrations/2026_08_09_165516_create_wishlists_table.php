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
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->foreign('user_id', 'wishlists_user_id_fk')->references('id')->on('users')->onDelete('cascade');
            $table->string('session_token')->nullable()->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->foreign('product_id', 'wishlists_product_id_fk')->references('id')->on('products')->onDelete('cascade');
            $table->timestamps();
            
            // Ensure no duplicate product per user or session
            $table->unique(['user_id', 'product_id'], 'wishlists_user_product_unique');
            $table->unique(['session_token', 'product_id'], 'wishlists_session_product_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
