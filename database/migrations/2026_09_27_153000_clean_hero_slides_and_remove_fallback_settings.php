<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Decode any escaped unicode / json strings in hero_slides
        try {
            $slides = DB::table('hero_slides')->get();
            foreach ($slides as $slide) {
                $updates = [];

                if (!empty($slide->title)) {
                    $decoded = json_decode($slide->title);
                    if (is_string($decoded)) {
                        $updates['title'] = $decoded;
                    } elseif (str_contains($slide->title, '\u')) {
                        $cleaned = stripcslashes(trim($slide->title, '"\''));
                        $updates['title'] = $cleaned ?: 'جمال يزهر في كل مناسبة';
                    }
                }

                if (!empty($slide->subtitle)) {
                    $decoded = json_decode($slide->subtitle);
                    if (is_string($decoded)) {
                        $updates['subtitle'] = $decoded;
                    } elseif (str_contains($slide->subtitle, '\u')) {
                        $cleaned = stripcslashes(trim($slide->subtitle, '"\''));
                        $updates['subtitle'] = $cleaned ?: 'اكتشف تشكيلتنا الفاخرة من الزهور والهدايا المصممة بعناية لتناسب جميع مناسباتك وتوصل المشاعر بكل رقة.';
                    }
                }

                if (!empty($slide->image_path)) {
                    $decoded = json_decode($slide->image_path);
                    if (is_string($decoded)) {
                        $updates['image_path'] = $decoded;
                    }
                }

                if (!empty($updates)) {
                    DB::table('hero_slides')->where('id', $slide->id)->update($updates);
                }
            }

            // 2. Ensure first slide has clean Arabic text & CTA button
            $firstSlide = DB::table('hero_slides')->orderBy('sort_order', 'asc')->orderBy('id', 'asc')->first();
            if ($firstSlide) {
                if (str_contains((string)$firstSlide->title, '\u') || empty($firstSlide->title)) {
                    DB::table('hero_slides')->where('id', $firstSlide->id)->update([
                        'title' => 'جمال يزهر في كل مناسبة',
                        'subtitle' => 'اكتشف تشكيلتنا الفاخرة من الزهور والهدايا المصممة بعناية لتناسب جميع مناسباتك وتوصل المشاعر بكل رقة.',
                        'button_text' => 'تسوق الآن',
                        'button_link' => '/catalog',
                    ]);
                }

                // 3. Ensure button text and link exist ONLY on the first slide
                DB::table('hero_slides')->where('id', '!=', $firstSlide->id)->update([
                    'button_text' => null,
                    'button_link' => null,
                ]);
            }

            // 4. Remove obsolete hero settings from settings table
            DB::table('settings')->whereIn('key', [
                'home_hero_image',
                'home_hero_title',
                'home_hero_subtitle',
            ])->delete();
        } catch (\Throwable $e) {
            // Ignore during dry testing or isolated execution
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
