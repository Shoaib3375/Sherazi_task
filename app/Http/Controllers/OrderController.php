<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use Illuminate\Support\Facades\Cache;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items'       => 'required|array',
        ]);

        return DB::transaction(function () use ($request) {
            $totalAmount = 0;

            $order = Order::create([
                'customer_id'  => $request->customer_id,
                'total_amount' => 0,
                'status'       => 'pending',
            ]);

            foreach ($request->items as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);

                if (!$product || $product->stock < $item['quantity']) {
                    throw new \Exception('Product ' . ($product->name ?? 'ID ' . $item['product_id']) . ' unavailable or out of stock');
                }

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $product->price,
                ]);

                $product->decrement('stock', $item['quantity']);
                $product->increment('sold_count', $item['quantity']);

                $totalAmount += $product->price * $item['quantity'];
            }

            $order->update(['total_amount' => $totalAmount]);

            Cache::forget('dashboard_stats');
            Cache::flush();

            return response()->json($order, 201);
        });
    }

    public function index()
    {
        $orders = Order::with('customer')
            ->withCount('items')
            ->paginate(15);

        return OrderResource::collection($orders);
    }

    public function filterByStatus(Request $request)
    {
        $status = $request->input('status');

        $orders = Order::with('customer')
            ->where('status', $status)
            ->paginate(15);

        return OrderResource::collection($orders);
    }
}
