<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\StockHistory;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryStock::with(['product', 'store']);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('quantity', 'like', "%{$search}%")
                    ->orWhere('alert_quantity', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($productQuery) use ($search) {
                        $productQuery->where('name', 'ilike', "%{$search}%")
                            ->orWhere('sku', 'ilike', "%{$search}%")
                            ->orWhere('barcode', 'ilike', "%{$search}%");
                    });
            });
        }
        $totalProducts = (clone $query)->count();
        $lowStock = (clone $query)
            ->where('quantity', '>', 0)
            ->whereColumn('quantity', '<=', 'alert_quantity')
            ->count();
        $outOfStock = (clone $query)
            ->where('quantity', '<=', 0)
            ->count();
        $inventory = $query
            ->latest()
            ->paginate(25);
        return response()->json([
            'message' => 'Inventory retrieved successfully',

            'summary' => [
                'total_products' => $totalProducts,
                'low_stock' => $lowStock,
                'out_of_stock' => $outOfStock,
            ],

            'data' => $inventory,
        ]);
    }
    public function show(InventoryStock $inventoryStock)
    {
        $inventoryStock->load(['product', 'store']);
        return response()->json([
            'message' => 'Inventory retrieved successfully.',
            'data' => $inventoryStock
        ]);
    }
    public function history(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['sometimes', 'integer', 'exists:products,id'],
        ]);

        $history = StockHistory::with(['user', 'store', 'product.brand', 'product.category'])
            ->when(
                isset($validated['product_id']),
                fn($query) => $query->where('product_id', $validated['product_id'])
            )
            ->latest()
            ->paginate(20);

        return response()->json([
            'message' => 'Stock history retrieved successfully.',
            'data' => $history
        ]);
    }
}
