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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->foreign('user_id', 'orders_user_id_fk')->references('id')->on('users')->onDelete('set null');
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->unsignedBigInteger('delivery_area_id')->index();
            $table->foreign('delivery_area_id', 'orders_delivery_area_id_fk')->references('id')->on('delivery_areas')->onDelete('restrict');
            $table->text('delivery_address');
            $table->date('delivery_date');
            $table->string('delivery_time_slot');
            $table->text('card_message')->nullable();
            $table->integer('subtotal'); // in whole SYP
            $table->integer('delivery_fee'); // in whole SYP
            $table->integer('total'); // in whole SYP
            $table->string('status')->default('pending')->index(); // order_status
            $table->string('payment_method'); // payment_method
            $table->string('payment_status')->default('pending')->index(); // payment_status
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
