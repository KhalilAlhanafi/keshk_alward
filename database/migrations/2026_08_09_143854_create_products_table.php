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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->index();
            $table->foreign('category_id', 'products_category_id_fk')->references('id')->on('categories')->onDelete('cascade');
            $table->string('name_ar');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('sku')->unique()->index();
            $table->integer('base_price')->index(); // in whole SYP
            $table->string('image_path')->nullable();
            $table->boolean('is_best_seller')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
