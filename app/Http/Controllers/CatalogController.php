<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CatalogController extends Controller
{
    /**
     * Display a listing of products with filters.
     * Results are cached per unique filter/sort/page combination (15 min TTL).
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse|\Illuminate\View\View
    {
        // Build a normalized params array for the query
        $params = [
            'category'   => $request->input('category') ?: $request->input('category_id'),
            'search'     => $request->input('search') ?: $request->input('q'),
            'min_price'  => $request->input('min_price'),
            'max_price'  => $request->input('max_price'),
            'sort_by'    => $request->input('sort_by') ?: $request->input('sort', 'newest'),
            'page'       => $request->input('page', 1),
            'per_page'   => 12,
        ];

        // Active parent categories for filter sidebar
        try {
            $categories = Cache::remember(CacheService::KEY_CATEGORIES_ACTIVE, CacheService::TTL_CATEGORIES, function () {
                return \App\Models\Category::whereNull('parent_id')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();
            });
        } catch (\Throwable $e) {
            Log::warning('CatalogController: Cache failed for categories, falling back to DB.', ['error' => $e->getMessage()]);
            $categories = \App\Models\Category::whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        }

        // Query logic
        $query = Product::with(['category', 'sizes'])->where('is_active', true);

        // Category filter
        if (!empty($params['category'])) {
            if (is_numeric($params['category'])) {
                $query->where('category_id', (int) $params['category']);
            } else {
                $cat = \App\Models\Category::where('slug', $params['category'])->first();
                if ($cat) {
                    $query->where('category_id', $cat->id);
                }
            }
        }

        // Arabic search filter
        if (!empty($params['search'])) {
            $searchTerm = '%' . trim($params['search']) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name_ar', 'like', $searchTerm)
                  ->orWhere('description', 'like', $searchTerm);
            });
        }

        // Min & Max Price filter
        if (!empty($params['min_price'])) {
            $query->where('base_price', '>=', (int) $params['min_price']);
        }
        if (!empty($params['max_price'])) {
            $query->where('base_price', '<=', (int) $params['max_price']);
        }

        // Sorting
        switch ($params['sort_by']) {
            case 'price_asc':
                $query->orderBy('base_price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('base_price', 'desc');
                break;
            case 'best_seller':
                $query->orderBy('is_best_seller', 'desc')->orderBy('created_at', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $products = $query->paginate($params['per_page'])->withQueryString();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'products' => $products->items(),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page'    => $products->lastPage(),
                    'per_page'     => $products->perPage(),
                    'total'        => $products->total(),
                    'next_page_url' => $products->nextPageUrl(),
                    'prev_page_url' => $products->previousPageUrl(),
                ],
            ]);
        }

        $allProducts = Product::with(['category', 'sizes'])->where('is_active', true)->orderBy('created_at', 'desc')->get();

        return view('catalog.index', compact('products', 'categories', 'params', 'allProducts'));
    }

    /**
     * Display a single product with sizes, active addons, and related products.
     */
    public function show(Request $request, $slug): \Illuminate\Http\JsonResponse|\Illuminate\View\View
    {
        $query = Product::with(['category', 'sizes', 'addons' => function ($query) {
            $query->where('is_active', true);
        }])->where(function($q) use ($slug) {
            $q->where('slug', $slug)->orWhere('id', is_numeric($slug) ? (int)$slug : 0);
        });

        // Allow authenticated admin to preview inactive / hidden products
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        if (!$user || !$user->isAdmin()) {
            $query->where('is_active', true);
        }

        $product = $query->firstOrFail();

        // Related products in same category
        $relatedProducts = Product::where('is_active', true)
            ->where('id', '!=', $product->id)
            ->when($product->category_id, function($q) use ($product) {
                $q->where('category_id', $product->category_id);
            })
            ->take(4)
            ->get();

        if ($relatedProducts->isEmpty()) {
            $relatedProducts = Product::where('is_active', true)->where('id', '!=', $product->id)->take(4)->get();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'product' => $product,
                'related_products' => $relatedProducts
            ]);
        }

        return view('catalog.show', compact('product', 'relatedProducts'));
    }

    /**
     * Compute live price for product page (size + addons).
     */
    public function pricePreview(Request $request, $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        
        $basePrice = $product->base_price;
        $sizeId = $request->input('size_id');
        $addonIds = $request->input('addons', []);

        // Override base price with size price if a size is selected
        if ($sizeId) {
            $size = $product->sizes()->where('id', $sizeId)->first();
            if ($size) {
                $basePrice = $size->price;
            }
        }

        // Add addons prices
        $addonsTotal = 0;
        if (!empty($addonIds)) {
            $addonsTotal = $product->addons()
                ->whereIn('addons.id', $addonIds)
                ->sum('price');
        }

        $total = $basePrice + $addonsTotal;

        return response()->json([
            'price' => $total,
            'formatted_price' => format_money($total),
        ]);
    }
}
