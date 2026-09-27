<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class HeroSlideController extends Controller
{
    /**
     * Process slide image and save to disk as an optimized WebP file.
     */
    private function processSlideImage(UploadedFile $file): string
    {
        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getRealPath());
            // Scale down to max 1920x1080 keeping aspect ratio
            $image->scaleDown(1920, 1080);
            $webpContent = $image->toWebp(85)->toString();
            $path = 'hero_slides/' . Str::uuid()->toString() . '.webp';
            Storage::disk('public')->put($path, $webpContent);
            return $path;
        } catch (\Throwable $e) {
            return $file->store('hero_slides', 'public');
        }
    }

    /**
     * Display a listing of all hero slides.
     */
    public function index(Request $request): View|JsonResponse
    {
        $slides = HeroSlide::orderBy('sort_order', 'asc')->orderBy('id', 'asc')->get();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($slides);
        }

        return view('admin.hero-slides.index', compact('slides'));
    }

    /**
     * Show form for creating a new hero slide (or use index inline).
     */
    public function create(): View
    {
        return view('admin.hero-slides.create');
    }

    /**
     * Store a newly created hero slide in storage.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'image' => ['required_without:image_url', 'nullable', 'image', 'max:25600'],
            'image_url' => ['required_without:image', 'nullable', 'string', 'max:2000'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->processSlideImage($request->file('image'));
        } elseif ($request->filled('image_url')) {
            $imagePath = $request->input('image_url');
        }

        // New slides do not have buttons; button exists only on the first slide
        $slide = HeroSlide::create([
            'image_path' => $imagePath,
            'title' => $request->input('title'),
            'subtitle' => $request->input('subtitle'),
            'button_text' => null,
            'button_link' => null,
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'تمت إضافة شريحة السلايدر بنجاح.',
                'slide' => $slide,
            ], 201);
        }

        return redirect()->route('admin.hero-slides.index')->with('success', 'تمت إضافة شريحة السلايدر بنجاح.');
    }

    /**
     * Show the form for editing the specified hero slide.
     */
    public function edit(HeroSlide $heroSlide): View
    {
        $firstSlide = HeroSlide::orderBy('sort_order', 'asc')->orderBy('id', 'asc')->first();
        $isFirst = ($firstSlide && $firstSlide->id === $heroSlide->id);

        return view('admin.hero-slides.edit', compact('heroSlide', 'isFirst'));
    }

    /**
     * Update the specified hero slide in storage.
     */
    public function update(Request $request, HeroSlide $heroSlide): RedirectResponse|JsonResponse
    {
        $firstSlide = HeroSlide::orderBy('sort_order', 'asc')->orderBy('id', 'asc')->first();
        $isFirst = ($firstSlide && $firstSlide->id === $heroSlide->id);

        $rules = [
            'image' => ['nullable', 'image', 'max:25600'],
            'image_url' => ['nullable', 'string', 'max:2000'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if ($isFirst) {
            $rules['button_text'] = ['nullable', 'string', 'max:100'];
            $rules['button_link'] = ['nullable', 'string', 'max:500'];
        }

        $request->validate($rules);

        $imagePath = $heroSlide->image_path;

        if ($request->hasFile('image')) {
            // Delete old file if locally stored
            if ($heroSlide->image_path && 
                !str_starts_with($heroSlide->image_path, 'data:') && 
                !str_starts_with($heroSlide->image_path, 'http://') && 
                !str_starts_with($heroSlide->image_path, 'https://')) {
                Storage::disk('public')->delete($heroSlide->image_path);
            }
            $imagePath = $this->processSlideImage($request->file('image'));
        } elseif ($request->filled('image_url')) {
            $imagePath = $request->input('image_url');
        }

        $heroSlide->update([
            'image_path' => $imagePath,
            'title' => $request->input('title'),
            'subtitle' => $request->input('subtitle'),
            'button_text' => $isFirst ? $request->input('button_text', 'تسوق الآن') : null,
            'button_link' => $isFirst ? $request->input('button_link', '/catalog') : null,
            'sort_order' => (int) $request->input('sort_order', $heroSlide->sort_order),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $heroSlide->is_active,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'تم تعديل شريحة السلايدر بنجاح.',
                'slide' => $heroSlide,
            ]);
        }

        return redirect()->route('admin.hero-slides.index')->with('success', 'تم تعديل شريحة السلايدر بنجاح.');
    }

    /**
     * Remove the specified hero slide from storage.
     */
    public function destroy(Request $request, HeroSlide $heroSlide): RedirectResponse|JsonResponse
    {
        $firstSlide = HeroSlide::orderBy('sort_order', 'asc')->orderBy('id', 'asc')->first();
        if ($firstSlide && $firstSlide->id === $heroSlide->id) {
            $msg = 'لا يمكن حذف الشريحة الأولى الأساسية، يمكنك تعديلها فقط.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => $msg], 422);
            }
            return redirect()->route('admin.hero-slides.index')->with('error', $msg);
        }

        if ($heroSlide->image_path && 
            !str_starts_with($heroSlide->image_path, 'data:') && 
            !str_starts_with($heroSlide->image_path, 'http://') && 
            !str_starts_with($heroSlide->image_path, 'https://')) {
            Storage::disk('public')->delete($heroSlide->image_path);
        }

        $heroSlide->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'تم حذف شريحة السلايدر بنجاح.',
            ]);
        }

        return redirect()->route('admin.hero-slides.index')->with('success', 'تم حذف شريحة السلايدر بنجاح.');
    }

    /**
     * Toggle active/inactive status of a slide.
     */
    public function toggleStatus(Request $request, HeroSlide $heroSlide): JsonResponse
    {
        $heroSlide->is_active = !$heroSlide->is_active;
        $heroSlide->save();

        $message = $heroSlide->is_active 
            ? 'تم تفعيل الشريحة بنجاح' 
            : 'تم إخفاء الشريحة بنجاح';

        return response()->json([
            'success' => true,
            'is_active' => $heroSlide->is_active,
            'message' => $message,
        ]);
    }
}
