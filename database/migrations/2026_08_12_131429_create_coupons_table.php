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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('type', ['percentage', 'fixed']);
            $table->integer('value'); // Percentage (0-100) or fixed amount (SYP)
            $table->integer('minimum_order_value')->default(0);
            $table->integer('max_discount_amount')->nullable(); // For percentage coupons
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->integer('usage_limit')->nullable(); // Total usage limit
            $table->integer('usage_count')->default(0); // Current usage count
            $table->integer('per_user_limit')->default(1); // Limit per user
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
