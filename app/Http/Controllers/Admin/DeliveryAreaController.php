<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryArea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryAreaController extends Controller
{
    /**
     * Display a listing of delivery areas.
     */
    public function index(): JsonResponse
    {
        $areas = DeliveryArea::orderBy('city_ar')->orderBy('area_ar')->get();
        return response()->json($areas);
    }

    /**
     * Store a newly created delivery area.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'city_ar' => ['required', 'string', 'max:255'],
            'area_ar' => ['required', 'string', 'max:255'],
            'delivery_fee' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $area = DeliveryArea::create([
            'city_ar' => $request->input('city_ar'),
            'area_ar' => $request->input('area_ar'),
            'delivery_fee' => $request->input('delivery_fee'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'message' => 'تم إنشاء منطقة التوصيل بنجاح.',
            'delivery_area' => $area,
        ], 201);
    }

    /**
     * Display the specified delivery area.
     */
    public function show(DeliveryArea $deliveryArea): JsonResponse
    {
        return response()->json($deliveryArea);
    }

    /**
     * Update the specified delivery area.
     */
    public function update(Request $request, DeliveryArea $deliveryArea): JsonResponse
    {
        $request->validate([
            'city_ar' => ['required', 'string', 'max:255'],
            'area_ar' => ['required', 'string', 'max:255'],
            'delivery_fee' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $deliveryArea->update([
            'city_ar' => $request->input('city_ar'),
            'area_ar' => $request->input('area_ar'),
            'delivery_fee' => $request->input('delivery_fee'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'message' => 'تم تحديث منطقة التوصيل بنجاح.',
            'delivery_area' => $deliveryArea,
        ]);
    }

    /**
     * Remove the specified delivery area.
     */
    public function destroy(DeliveryArea $deliveryArea): JsonResponse
    {
        $deliveryArea->delete();

        return response()->json([
            'message' => 'تم حذف منطقة التوصيل بنجاح.'
        ]);
    }
}
