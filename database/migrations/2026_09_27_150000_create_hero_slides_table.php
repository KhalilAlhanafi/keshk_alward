<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->longText('image_path');
            $table->string('title')->nullable();
            $table->text('subtitle')->nullable();
            $table->string('button_text')->nullable()->default('تسوق الآن');
            $table->string('button_link')->nullable()->default('/catalog');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed current hero setting if available so the homepage retains its content seamlessly
        try {
            $heroImg = DB::table('settings')->where('key', 'home_hero_image')->value('value');
            $heroTitle = DB::table('settings')->where('key', 'home_hero_title')->value('value') ?: 'جمال يزهر في كل مناسبة';
            $heroSubtitle = DB::table('settings')->where('key', 'home_hero_subtitle')->value('value') ?: 'اكتشف تشكيلتنا الفاخرة من الزهور والهدايا المصممة بعناية لتناسب جميع مناسباتك وتوصل المشاعر بكل رقة.';

            DB::table('hero_slides')->insert([
                'image_path' => $heroImg ?: 'https://images.unsplash.com/photo-1487530811015-780930f87e8f?auto=format&fit=crop&w=1600&q=80',
                'title' => $heroTitle,
                'subtitle' => $heroSubtitle,
                'button_text' => 'تسوق الآن',
                'button_link' => '/catalog',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Ignore during dry tests or isolated runs
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hero_slides');
    }
};
