<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_addons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_item_id')->index();
            $table->foreign('order_item_id', 'oia_order_item_id_fk')
                ->references('id')->on('order_items')->onDelete('cascade');
            $table->string('addon_name_snapshot');    // name at time of order
            $table->integer('price_snapshot');        // SYP price at time of order
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_addons');
    }
};
