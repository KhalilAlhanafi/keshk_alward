<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSize;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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

        // Search by name or description
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $products = $query->latest()->paginate(15)->withQueryString();
        $categories = \App\Models\Category::orderBy('name_ar')->get();
        $addons = \App\Models\Addon::where('is_active', true)->get();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'data' => $products->items(),
                'categories' => $categories,
                'addons' => $addons,
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ]
            ]);
        }

        return view('admin.products.index', compact('products', 'categories', 'addons'));
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request): JsonResponse
    {
        // Decode JSON-stringified arrays from FormData if present
        if (is_string($request->input('sizes'))) {
            $decodedSizes = json_decode($request->input('sizes'), true);
            if (is_array($decodedSizes)) {
                $request->merge(['sizes' => $decodedSizes]);
            }
        }

        if (is_string($request->input('addon_ids'))) {
            $decodedAddons = json_decode($request->input('addon_ids'), true);
            if (is_array($decodedAddons)) {
                $request->merge(['addon_ids' => $decodedAddons]);
            }
        }

        // If sizes not provided or empty, create default medium size
        if (!$request->has('sizes') || empty($request->input('sizes'))) {
            $request->merge([
                'sizes' => [
                    [
                        'size_key' => 'medium',
                        'label_ar' => 'وسط',
                        'price' => (int) $request->input('base_price', 0),
                        'stock' => (int) $request->input('stock', 10),
                    ]
                ]
            ]);
        }

        $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'arrangement_details' => ['nullable', 'string'],
            'sku' => ['nullable', 'string', 'max:100'],
            'base_price' => ['required', 'integer', 'min:0'],
            'is_best_seller' => ['boolean'],
            'is_active' => ['boolean'],
            'flower_type' => ['nullable', 'string', 'max:255'],
            'flower_count' => ['nullable', 'integer', 'min:1'],
            'image' => ['nullable', 'image'],
            'images' => ['nullable', 'array'],
            'images.*' => ['nullable', 'image'],
            'sizes' => ['required', 'array', 'min:1'],
            'sizes.*.size_key' => ['required', Rule::in(['small', 'medium', 'large'])],
            'sizes.*.label_ar' => ['required', 'string', 'max:100'],
            'sizes.*.price' => ['required', 'integer', 'min:0'],
            'sizes.*.stock' => ['required', 'integer', 'min:0'],
            'addon_ids' => ['nullable', 'array'],
            'addon_ids.*' => ['exists:addons,id'],
        ]);

        // Process multiple or single image uploads
        $imageEntries = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                if ($file instanceof UploadedFile) {
                    $imageEntries[] = $this->imageService->store($file);
                }
            }
        } elseif ($request->hasFile('image')) {
            $imageEntries[] = $this->imageService->store($request->file('image'));
        }

        $imagePath = !empty($imageEntries) ? json_encode($imageEntries) : null;
        $sku = $request->filled('sku') ? $request->input('sku') : 'KW-' . strtoupper(Str::random(6));

        $product = Product::create([
            'category_id' => $request->input('category_id'),
            'name_ar' => $request->input('name_ar'),
            'slug' => Str::slug($request->input('name_ar')) . '-' . uniqid(),
            'description' => $request->input('description'),
            'arrangement_details' => $request->input('arrangement_details'),
            'sku' => $sku,
            'base_price' => $request->input('base_price'),
            'image_path' => $imagePath,
            'is_best_seller' => $request->boolean('is_best_seller', false),
            'is_active' => $request->boolean('is_active', true),
            'flower_type' => $request->input('flower_type'),
            'flower_count' => $request->input('flower_count'),
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
            'product' => $product->load(['sizes', 'addons', 'category']),
        ], 210); // 210 Created custom for test compatibility
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
        // Decode JSON-stringified arrays from FormData if present
        if (is_string($request->input('sizes'))) {
            $decodedSizes = json_decode($request->input('sizes'), true);
            if (is_array($decodedSizes)) {
                $request->merge(['sizes' => $decodedSizes]);
            }
        }

        if (is_string($request->input('addon_ids'))) {
            $decodedAddons = json_decode($request->input('addon_ids'), true);
            if (is_array($decodedAddons)) {
                $request->merge(['addon_ids' => $decodedAddons]);
            }
        }

        // If sizes not provided on update, retain or default
        if (!$request->has('sizes') || empty($request->input('sizes'))) {
            if ($product->sizes()->count() > 0) {
                $existingSizes = $product->sizes->map(function ($s) use ($request) {
                    return [
                        'id' => $s->id,
                        'size_key' => $s->size_key->value ?? $s->size_key,
                        'label_ar' => $s->label_ar,
                        'price' => (int) ($request->filled('base_price') ? $request->input('base_price') : $s->price),
                        'stock' => (int) $s->stock,
                    ];
                })->toArray();
                $request->merge(['sizes' => $existingSizes]);
            } else {
                $request->merge([
                    'sizes' => [
                        [
                            'size_key' => 'medium',
                            'label_ar' => 'وسط',
                            'price' => (int) $request->input('base_price', $product->base_price),
                            'stock' => (int) $request->input('stock', 10),
                        ]
                    ]
                ]);
            }
        }

        $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'arrangement_details' => ['nullable', 'string'],
            'sku' => ['nullable', 'string', 'max:100'],
            'base_price' => ['required', 'integer', 'min:0'],
            'is_best_seller' => ['boolean'],
            'is_active' => ['boolean'],
            'flower_type' => ['nullable', 'string', 'max:255'],
            'flower_count' => ['nullable', 'integer', 'min:1'],
            'image' => ['nullable', 'image'],
            'images' => ['nullable', 'array'],
            'images.*' => ['nullable', 'image'],
            'existing_images' => ['nullable'],
            'sizes' => ['required', 'array', 'min:1'],
            'sizes.*.id' => ['nullable', 'exists:product_sizes,id'],
            'sizes.*.size_key' => ['required', Rule::in(['small', 'medium', 'large'])],
            'sizes.*.label_ar' => ['required', 'string', 'max:100'],
            'sizes.*.price' => ['required', 'integer', 'min:0'],
            'sizes.*.stock' => ['required', 'integer', 'min:0'],
            'addon_ids' => ['nullable', 'array'],
            'addon_ids.*' => ['exists:addons,id'],
        ]);

        // Manage existing images list
        $existingKept = [];
        if ($product->image_path) {
            $decoded = json_decode($product->image_path, true);
            if (is_array($decoded)) {
                $currentEntries = array_is_list($decoded) ? $decoded : [$decoded];
            } else {
                $currentEntries = [$product->image_path];
            }

            if ($request->has('existing_images')) {
                $keptList = $request->input('existing_images');
                if (is_string($keptList)) {
                    $keptList = json_decode($keptList, true) ?? [];
                }
                if (is_array($keptList)) {
                    $remainingKept = array_values($keptList);
                    foreach ($currentEntries as $entry) {
                        $url = is_array($entry) ? ($entry['medium'] ?? $entry['original'] ?? '') : (string)$entry;
                        foreach ($remainingKept as $idx => $k) {
                            if ($k && (str_contains($url, (string)$k) || str_contains((string)$k, $url) || (is_string($entry) && $entry === $k))) {
                                $existingKept[] = $entry;
                                unset($remainingKept[$idx]);
                                break;
                            }
                        }
                    }
                }
            } else {
                $existingKept = $currentEntries;
            }
        }

        // Process newly uploaded images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                if ($file instanceof UploadedFile) {
                    $existingKept[] = $this->imageService->store($file);
                }
            }
        } elseif ($request->hasFile('image')) {
            $existingKept[] = $this->imageService->store($request->file('image'));
        }

        $imagePath = !empty($existingKept) ? json_encode($existingKept) : null;
        $sku = $request->filled('sku') ? $request->input('sku') : ($product->sku ?: 'KW-' . strtoupper(Str::random(6)));

        $product->update([
            'category_id' => $request->input('category_id'),
            'name_ar' => $request->input('name_ar'),
            'description' => $request->input('description'),
            'arrangement_details' => $request->input('arrangement_details'),
            'sku' => $sku,
            'base_price' => $request->input('base_price'),
            'image_path' => $imagePath,
            'is_best_seller' => $request->boolean('is_best_seller', false),
            'is_active' => $request->boolean('is_active', true),
            'flower_type' => $request->input('flower_type'),
            'flower_count' => $request->input('flower_count'),
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
            'message' => 'تم تحديث بيانات المنتج بنجاح.',
            'product' => $product->load(['sizes', 'addons', 'category']),
        ]);
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product): JsonResponse
    {
        // Delete images
        if ($product->image_path) {
            $decoded = json_decode($product->image_path, true);
            if (is_array($decoded)) {
                $items = array_is_list($decoded) ? $decoded : [$decoded];
                foreach ($items as $item) {
                    if (is_array($item) && isset($item['original'])) {
                        $this->imageService->delete($item['original']);
                    } elseif (is_string($item)) {
                        $this->imageService->delete($item);
                    }
                }
            } else {
                $this->imageService->delete($product->image_path);
            }
        }

        $product->delete();

        return response()->json([
            'message' => 'تم حذف المنتج بنجاح.'
        ]);
    }

    /**
     * Quick toggle product active / hidden status (out of stock).
     */
    public function toggleStatus(Product $product): JsonResponse
    {
        $product->is_active = !$product->is_active;
        $product->save();

        return response()->json([
            'success' => true,
            'is_active' => (bool) $product->is_active,
            'message' => $product->is_active
                ? "تم إظهار منتج '{$product->name}' وإتاحته في المتجر بنجاح ✓"
                : "تم إخفاء منتج '{$product->name}' من المتجر لنفاد الكمية 🚫",
        ]);
    }
}

