<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\Product;

class PosController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();
        $totalproduct = Product::count();
        $statusactive = Product::where('status', 'active')->count();
        $statusinactive = Product::where('status', 'inactive')->count();
        $checkinstock = Product::withSum('inventoryStocks', 'quantity')->latest()->paginate(10);
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('cost_price', 'like', "%{$search}%")
                    ->orWhere('sale_price', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        $product = $query
            ->latest()
            ->paginate(10);
        return response()->json([
            'message' => 'POS Product received successfully.',
            'summary' => [
                'total_products' => $totalproduct,
                'active_products' => $statusactive,
                'inactive_products' => $statusinactive,
                'total_stock' => $checkinstock,
            ],
            'data' => $product
        ]);
    }
    public function filltercategory(Request $request, $category)
    {
        $o = Category::with(['products'
        => function ($query) {
            $query->where('status', 'active');
        }])->findOrFail($category);

        return response()->json([
            'message' => 'Products filtered by category successfully',
            'data' => $o
        ]);
    }
    public function show(Product $product)
    {
        $product->load([
            'category',
            'brand'
        ]);
        return response()->json([
            'message' => 'POS Product Detail received successfully.',
            'data' => $product
        ]);
    }
}
