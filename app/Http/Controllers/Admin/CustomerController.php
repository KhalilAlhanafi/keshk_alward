<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse|\Illuminate\View\View
    {
        $query = User::where('role', 'customer');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->withCount('orders')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        if ($request->wantsJson() || $request->ajax()) {
            $items = collect($customers->items())->map(function ($user) {
                $totalSpend = $user->orders()->whereIn('status', ['delivered', 'confirmed'])->sum('total');
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'orders_count' => $user->orders_count,
                    'total_spend' => $totalSpend,
                    'formatted_total_spend' => format_money($totalSpend),
                    'created_at' => $user->created_at->format('Y-m-d H:i'),
                ];
            });

            return response()->json([
                'data' => $items,
                'pagination' => [
                    'current_page' => $customers->currentPage(),
                    'last_page' => $customers->lastPage(),
                    'per_page' => $customers->perPage(),
                    'total' => $customers->total(),
                ]
            ]);
        }

        return view('admin.customers.index', compact('customers'));
    }

    /**
     * Display a customer's details and order history.
     */
    public function show(User $customer): JsonResponse
    {
        abort_if($customer->role !== 'customer' && !$customer->hasRole('customer'), 404);

        $orders = $customer->orders()
            ->with(['deliveryArea'])
            ->latest()
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'total' => $order->total,
                    'formatted_total' => format_money($order->total),
                    'status' => $order->status instanceof \BackedEnum ? $order->status->value : (string)$order->status,
                    'created_at' => $order->created_at->format('Y-m-d H:i'),
                ];
            });

        $totalSpend = $customer->orders()->whereIn('status', ['delivered', 'confirmed'])->sum('total');

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'total_spend' => $totalSpend,
                'formatted_total_spend' => format_money($totalSpend),
                'created_at' => $customer->created_at->format('Y-m-d H:i'),
            ],
            'orders' => $orders,
        ]);
    }
}
