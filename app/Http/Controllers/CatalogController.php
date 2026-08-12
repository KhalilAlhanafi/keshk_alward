<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CatalogController extends Controller
{
    /**
     * Display a listing of products with filters.
     * Results are cached per unique filter/sort/page combination (15 min TTL).
     */
    public function index(Request $request): JsonResponse
    {
        // Build a normalized params array for the cache key
        $params = [
            'category_id' => $request->input('category_id'),
            'min_price'   => $request->input('min_price'),
            'max_price'   => $request->input('max_price'),
            'sort_by'     => $request->input('sort_by', 'newest'),
            'page'        => $request->input('page', 1),
            'per_page'    => 12,
        ];

        $result = CacheService::rememberCatalog($params, function () use ($params) {
            // Eager load category and sizes to prevent N+1 queries
            $query = Product::with(['category', 'sizes'])->where('is_active', true);

            // Filter by category
            if (!empty($params['category_id'])) {
                $query->where('category_id', (int) $params['category_id']);
            }

            // Filter by min price
            if (!empty($params['min_price'])) {
                $query->where('base_price', '>=', (int) $params['min_price']);
            }

            // Filter by max price
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

            $products = $query->paginate($params['per_page']);

            return [
                'products' => $products->items(),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page'    => $products->lastPage(),
                    'per_page'     => $products->perPage(),
                    'total'        => $products->total(),
                    'next_page_url' => $products->nextPageUrl(),
                    'prev_page_url' => $products->previousPageUrl(),
                ],
            ];
        });

        return response()->json($result);
    }

    /**
     * Display a single product with sizes and active addons.
     */
    public function show($slug): JsonResponse
    {
        $product = Product::with(['category', 'sizes', 'addons' => function ($query) {
            $query->where('is_active', true);
        }])->where('slug', $slug)
          ->where('is_active', true)
          ->firstOrFail();

        return response()->json($product);
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
