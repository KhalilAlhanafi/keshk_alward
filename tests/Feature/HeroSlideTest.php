<?php

namespace Tests\Feature;

use App\Models\HeroSlide;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HeroSlideTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customerUser;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create([
            'phone' => '0911111111',
            'role' => 'admin',
        ]);
        $this->adminUser->assignRole('admin');

        $this->customerUser = User::factory()->create([
            'phone' => '0922222222',
            'role' => 'customer',
        ]);
        $this->customerUser->assignRole('customer');
    }

    public function test_guest_and_customer_cannot_access_hero_slides_admin(): void
    {
        $response = $this->get('/admin/hero-slides');
        $response->assertRedirect('/login');

        $response = $this->actingAs($this->customerUser)->get('/admin/hero-slides');
        $response->assertStatus(403);
    }

    public function test_admin_can_view_hero_slides_list(): void
    {
        HeroSlide::create([
            'image_path' => 'https://example.com/banner1.jpg',
            'title' => 'شريحة تجريبية',
            'subtitle' => 'نص توضيحي',
            'button_text' => 'تسوق الآن',
            'button_link' => '/catalog',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->get('/admin/hero-slides');
        $response->assertStatus(200);
        $response->assertSee('شريحة تجريبية');
    }

    public function test_admin_can_create_hero_slide_with_image_upload_and_no_buttons(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('banner.jpg', 1200, 600);

        $response = $this->actingAs($this->adminUser)->postJson('/admin/hero-slides', [
            'image' => $file,
            'title' => 'عرض خاص على الورود',
            'subtitle' => 'خصومات حتى 30%',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('hero_slides', [
            'title' => 'عرض خاص على الورود',
            'button_text' => null,
            'button_link' => null,
        ]);
    }

    public function test_first_slide_cannot_be_deleted(): void
    {
        $firstSlide = HeroSlide::orderBy('sort_order', 'asc')->orderBy('id', 'asc')->first();
        if (!$firstSlide) {
            $firstSlide = HeroSlide::create([
                'image_path' => 'https://example.com/first.jpg',
                'title' => 'الشريحة الأولى',
                'sort_order' => 1,
            ]);
        }

        $secondSlide = HeroSlide::create([
            'image_path' => 'https://example.com/second.jpg',
            'title' => 'الشريحة الثانية',
            'sort_order' => 99,
        ]);

        // Attempting to delete the first slide fails with 422
        $response = $this->actingAs($this->adminUser)->deleteJson("/admin/hero-slides/{$firstSlide->id}");
        $response->assertStatus(422);
        $this->assertDatabaseHas('hero_slides', ['id' => $firstSlide->id]);

        // Deleting subsequent slides succeeds
        $response = $this->actingAs($this->adminUser)->deleteJson("/admin/hero-slides/{$secondSlide->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('hero_slides', ['id' => $secondSlide->id]);
    }

    public function test_admin_can_toggle_slide_active_status(): void
    {
        $slide = HeroSlide::create([
            'image_path' => 'https://example.com/slide.jpg',
            'title' => 'شريحة قابلة للتعطيل',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->patchJson("/admin/hero-slides/{$slide->id}/toggle-status");
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_active' => false,
        ]);

        $this->assertFalse($slide->fresh()->is_active);
    }

    public function test_homepage_loads_active_slides(): void
    {
        HeroSlide::create([
            'image_path' => 'https://example.com/active-banner.jpg',
            'title' => 'شريحة فعالة في الرئيسية',
            'subtitle' => 'وصف تفاعلي',
            'is_active' => true,
        ]);

        HeroSlide::create([
            'image_path' => 'https://example.com/inactive-banner.jpg',
            'title' => 'شريحة مخفية',
            'is_active' => false,
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('شريحة فعالة في الرئيسية');
        $response->assertDontSee('شريحة مخفية');
    }

    public function test_cache_is_flushed_when_slide_is_saved_or_deleted(): void
    {
        Cache::put(CacheService::KEY_HERO_SLIDES_ACTIVE, ['dummy']);
        $this->assertTrue(Cache::has(CacheService::KEY_HERO_SLIDES_ACTIVE));

        $slide = HeroSlide::create([
            'image_path' => 'https://example.com/cache-test.jpg',
            'title' => 'شريحة كاش',
        ]);

        $this->assertFalse(Cache::has(CacheService::KEY_HERO_SLIDES_ACTIVE));

        Cache::put(CacheService::KEY_HERO_SLIDES_ACTIVE, ['dummy2']);
        $slide->delete();
        $this->assertFalse(Cache::has(CacheService::KEY_HERO_SLIDES_ACTIVE));
    }
}
