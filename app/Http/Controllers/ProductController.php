<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Http\Resources\SalesReportResource;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Cache::remember('products_page_' . request('page', 1), 3600, function () {
            return Product::with('category')->paginate(15);
        });

        return ProductResource::collection($products);
    }

    public function salesReport()
    {
        $orderItems = OrderItem::with(['order.customer', 'product'])->paginate(15);

        return SalesReportResource::collection($orderItems);
    }

    public function dashboard()
    {
        return Cache::remember('dashboard_stats', 3600, function () {
            $totalProducts = Product::count();
            $totalOrders   = Order::count();
            $totalRevenue  = Order::sum('total_amount');
            $categories    = Category::all();

            $topProducts = Product::orderByDesc('sold_count')
                ->take(5)
                ->get();

            return response()->json([
                'total_products' => $totalProducts,
                'total_orders'   => $totalOrders,
                'total_revenue'  => $totalRevenue,
                'categories'     => $categories,
                'top_products'   => $topProducts,
            ])->getData();
        });
    }

    public function search(Request $request)
    {
        $keyword  = $request->input('q');
        $products = Product::with('category')
                           ->where('name', 'LIKE', '%' . $keyword . '%')
                           ->orWhere('description', 'LIKE', '%' . $keyword . '%')
                           ->paginate(15);

        return ProductResource::collection($products);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
        ]);

        $product = Product::create($request->all());

        Cache::forget('dashboard_stats');
        // Clear product pages cache if needed, but for simplicity we clear the main dashboard
        // In real world, we might use tags if supported by redis
        Cache::flush();

        return response()->json($product, 201);
    }
}
