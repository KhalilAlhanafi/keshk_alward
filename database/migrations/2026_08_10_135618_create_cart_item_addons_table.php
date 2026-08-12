<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_item_addons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_item_id')->index();
            $table->foreign('cart_item_id', 'cia_cart_item_id_fk')
                ->references('id')->on('cart_items')->onDelete('cascade');
            $table->unsignedBigInteger('addon_id')->index();
            $table->foreign('addon_id', 'cia_addon_id_fk')
                ->references('id')->on('addons')->onDelete('cascade');
            $table->integer('price_snapshot'); // SYP, whole integer
            $table->timestamps();

            $table->unique(['cart_item_id', 'addon_id'], 'cia_cart_item_addon_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_item_addons');
    }
};
