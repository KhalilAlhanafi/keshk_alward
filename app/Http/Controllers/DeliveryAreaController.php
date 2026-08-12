<?php

namespace App\Http\Controllers;

use App\Models\DeliveryArea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryAreaController extends Controller
{
    /**
     * Get delivery areas.
     * If 'city' is provided, return areas in that city.
     * Otherwise, return a list of unique active cities.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $city = $request->input('city');

        if ($city) {
            $areas = DeliveryArea::where('city_ar', $city)
                ->where('is_active', true)
                ->get(['id', 'area_ar', 'delivery_fee'])
                ->map(function ($area) {
                    return [
                        'id' => $area->id,
                        'area' => $area->area_ar,
                        'fee' => $area->delivery_fee,
                        'formatted_fee' => format_money($area->delivery_fee),
                    ];
                });

            return response()->json($areas);
        }

        $cities = DeliveryArea::where('is_active', true)
            ->distinct()
            ->pluck('city_ar');

        return response()->json($cities);
    }
}
