<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class CategoryController extends Controller
{
    /**
     * Process category image and convert to optimized WebP Base64 data URI.
     */
    private function processCategoryImage(UploadedFile $file): string
    {
        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getRealPath());
            // Scale down to max 600x600 keeping aspect ratio
            $image->scaleDown(600, 600);
            $webpContent = $image->toWebp(80)->toString();
            return 'data:image/webp;base64,' . base64_encode($webpContent);
        } catch (\Throwable $e) {
            $mimeType = $file->getMimeType() ?: 'image/jpeg';
            return 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
        }
    }

    /**
     * Display a listing of categories.
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse|\Illuminate\View\View
    {
        $categories = Category::with('parent')
            ->withCount('products')
            ->orderBy('sort_order')
            ->get();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($categories);
        }

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->processCategoryImage($request->file('image'));
        }

        $category = Category::create([
            'name_ar' => $request->input('name_ar'),
            'slug' => Str::slug($request->input('name_ar')) . '-' . uniqid(),
            'parent_id' => $request->input('parent_id'),
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active', true),
            'image_path' => $imagePath,
        ]);

        return response()->json([
            'message' => 'تم إنشاء القسم بنجاح.',
            'category' => $category->loadCount('products'),
        ], 201);
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category): JsonResponse
    {
        return response()->json($category->load(['children', 'parent'])->loadCount('products'));
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:categories,id', Rule::notIn([$category->id])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $imagePath = $category->image_path;
        if ($request->hasFile('image')) {
            $imagePath = $this->processCategoryImage($request->file('image'));
        }

        $category->update([
            'name_ar' => $request->input('name_ar'),
            'parent_id' => $request->input('parent_id'),
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active', true),
            'image_path' => $imagePath,
        ]);

        return response()->json([
            'message' => 'تم تحديث بيانات القسم بنجاح.',
            'category' => $category->loadCount('products'),
        ]);
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Category $category): JsonResponse
    {
        $productsCount = $category->products()->count();

        // Base64 images are stored in DB — no filesystem cleanup needed
        $category->delete();

        $msg = $productsCount > 0 
            ? "تم حذف القسم بنجاح (وتم معالجة {$productsCount} منتج مرتبط به)."
            : "تم حذف القسم بنجاح.";

        return response()->json([
            'message' => $msg,
            'deleted_id' => $category->id
        ]);
    }
}
