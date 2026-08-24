<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSize;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Display a listing of the products.
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse|\Illuminate\View\View
    {
        $query = Product::with(['category', 'sizes']);

        // Search by name
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $query->latest()->paginate(15)->withQueryString();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'data' => $products->items(),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ]
            ]);
        }

        return view('admin.products.index', compact('products'));
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sku' => ['required', 'string', 'unique:products,sku', 'max:100'],
            'base_price' => ['required', 'integer', 'min:0'],
            'is_best_seller' => ['boolean'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:4096'], // max 4MB
            'sizes' => ['required', 'array', 'min:1'],
            'sizes.*.size_key' => ['required', Rule::in(['small', 'medium', 'large'])],
            'sizes.*.label_ar' => ['required', 'string', 'max:100'],
            'sizes.*.price' => ['required', 'integer', 'min:0'],
            'sizes.*.stock' => ['required', 'integer', 'min:0'],
            'addon_ids' => ['nullable', 'array'],
            'addon_ids.*' => ['exists:addons,id'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            // Process and store multiple sizes and formats
            $paths = $this->imageService->store($request->file('image'));
            $imagePath = json_encode($paths);
        }

        $product = Product::create([
            'category_id' => $request->input('category_id'),
            'name_ar' => $request->input('name_ar'),
            'slug' => Str::slug($request->input('name_ar')) . '-' . uniqid(),
            'description' => $request->input('description'),
            'sku' => $request->input('sku'),
            'base_price' => $request->input('base_price'),
            'image_path' => $imagePath,
            'is_best_seller' => $request->boolean('is_best_seller', false),
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Create product sizes
        foreach ($request->input('sizes') as $sizeData) {
            $product->sizes()->create([
                'size_key' => $sizeData['size_key'],
                'label_ar' => $sizeData['label_ar'],
                'price' => $sizeData['price'],
                'stock' => $sizeData['stock'],
            ]);
        }

        // Sync addons
        if ($request->has('addon_ids')) {
            $product->addons()->sync($request->input('addon_ids'));
        }

        return response()->json([
            'message' => 'تم إنشاء المنتج بنجاح.',
            'product' => $product->load(['sizes', 'addons']),
        ], 210); // 210 Created custom
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): JsonResponse
    {
        return response()->json($product->load(['category', 'sizes', 'addons']));
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sku' => ['required', 'string', Rule::unique('products', 'sku')->ignore($product->id), 'max:100'],
            'base_price' => ['required', 'integer', 'min:0'],
            'is_best_seller' => ['boolean'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'sizes' => ['required', 'array', 'min:1'],
            'sizes.*.id' => ['nullable', 'exists:product_sizes,id'],
            'sizes.*.size_key' => ['required', Rule::in(['small', 'medium', 'large'])],
            'sizes.*.label_ar' => ['required', 'string', 'max:100'],
            'sizes.*.price' => ['required', 'integer', 'min:0'],
            'sizes.*.stock' => ['required', 'integer', 'min:0'],
            'addon_ids' => ['nullable', 'array'],
            'addon_ids.*' => ['exists:addons,id'],
        ]);

        $imagePath = $product->image_path;
        if ($request->hasFile('image')) {
            // Delete old image variants first
            if ($product->image_path) {
                $oldPaths = json_decode($product->image_path, true);
                if (is_array($oldPaths) && isset($oldPaths['original'])) {
                    $this->imageService->delete($oldPaths['original']);
                }
            }
            $paths = $this->imageService->store($request->file('image'));
            $imagePath = json_encode($paths);
        }

        $product->update([
            'category_id' => $request->input('category_id'),
            'name_ar' => $request->input('name_ar'),
            'description' => $request->input('description'),
            'sku' => $request->input('sku'),
            'base_price' => $request->input('base_price'),
            'image_path' => $imagePath,
            'is_best_seller' => $request->boolean('is_best_seller', false),
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Sync sizes: delete those not in request, update/create remaining
        $sizeIdsInRequest = collect($request->input('sizes'))->pluck('id')->filter()->toArray();
        $product->sizes()->whereNotIn('id', $sizeIdsInRequest)->delete();

        foreach ($request->input('sizes') as $sizeData) {
            if (isset($sizeData['id'])) {
                ProductSize::where('id', $sizeData['id'])->update([
                    'size_key' => $sizeData['size_key'],
                    'label_ar' => $sizeData['label_ar'],
                    'price' => $sizeData['price'],
                    'stock' => $sizeData['stock'],
                ]);
            } else {
                $product->sizes()->create([
                    'size_key' => $sizeData['size_key'],
                    'label_ar' => $sizeData['label_ar'],
                    'price' => $sizeData['price'],
                    'stock' => $sizeData['stock'],
                ]);
            }
        }

        // Sync addons
        if ($request->has('addon_ids')) {
            $product->addons()->sync($request->input('addon_ids'));
        }

        return response()->json([
            'message' => 'تم تحديث المنتج بنجاح.',
            'product' => $product->load(['sizes', 'addons']),
        ]);
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product): JsonResponse
    {
        // Delete images
        if ($product->image_path) {
            $oldPaths = json_decode($product->image_path, true);
            if (is_array($oldPaths) && isset($oldPaths['original'])) {
                $this->imageService->delete($oldPaths['original']);
            }
        }

        $product->delete();

        return response()->json([
            'message' => 'تم حذف المنتج بنجاح.'
        ]);
    }
}
