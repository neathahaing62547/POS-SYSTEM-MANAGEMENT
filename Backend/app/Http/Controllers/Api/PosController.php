<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Store;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Services\PosStoreResolver;

class PosController extends Controller
{
    public function __construct(private PosStoreResolver $posStoreResolver)
    {
    }
    public function index(Request $request)
    {
        $request->validate([
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);

        $storeId = $this->posStoreResolver->storeIdForUser($request->user()->id);
        $query = Product::query()
            ->where('status', 'active')
            ->withSum([
                'inventoryStocks as available_stock' => function ($stockQuery) use ($storeId) {
                    $stockQuery->where('store_id', $storeId);
                }
            ], 'quantity');
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        $totalproduct = Product::count();
        $statusactive = Product::where('status', 'active')->count();
        $statusinactive = Product::where('status', 'inactive')->count();
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        $products = $query
            ->latest()
            ->get();
        $store = Store::findOrFail($storeId);
        return response()->json([
            'message' => 'POS Product received successfully.',
            'store' => ['id' => $store->id, 'name' => $store->name],
            'summary' => [
                'total_products' => $totalproduct,
                'active_products' => $statusactive,
                'inactive_products' => $statusinactive,
                'filtered_products' => $products->count(),
            ],
            'data' => $products
        ]);
    }
    public function categories(Request $request)
    {
        $storeId = $this->posStoreResolver->storeIdForUser($request->user()->id);
        $categories = Category::query()
            ->where('status', 'active')
            ->whereHas('products', function ($productQuery) use ($storeId) {
                $productQuery
                    ->where('status', 'active')
                    ->whereHas('inventoryStocks', function ($stockQuery) use ($storeId) {
                        $stockQuery
                            ->where('store_id', $storeId)
                            ->where('quantity', '>', 0);
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'message' => 'POS categories received successfully.',
            'data' => $categories,
        ]);
    }

    public function filltercategory(Request $request, $category)
    {
        $category = Category::findOrFail($category);
        $storeId = $this->posStoreResolver->storeIdForUser($request->user()->id);
        $products = Product::query()
            ->where('category_id', $category->id)
            ->where('status', 'active')
            ->withSum([
                'inventoryStocks as available_stock' => function ($stockQuery) use ($storeId) {
                    $stockQuery->where('store_id', $storeId);
                }
            ], 'quantity')
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Products filtered by category successfully',
            'data' => [
                'category' => $category,
                'products' => $products,
            ],
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