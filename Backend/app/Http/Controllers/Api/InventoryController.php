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

            $query->where(
                function ($q) use ($search) {
                    $q->where('quantity', 'like', "%{$search}%")
                    ->orWhere('alert_quantity', 'like', "%{$search}%");
                }
            );
        }
        $inventory = $query
            ->latest()
            ->paginate(25);
        return response()->json([
            'message' => 'Inventory retrieved successfully',
            'total' => $inventory->count(),
            'totalproduct' => $inventory->sum('quantity'),
            'data' => $inventory
        ]);
    }
    public function show(InventoryStock $inventoryStock)
    {
        $inventoryStock->load(['product' , 'store']);
        return response()->json([
            'message' => 'Inventory retrieved successfully.',
            'data' => $inventoryStock
        ]);
    }
        public function history(Request $request)
    {
        $history = StockHistory::with([ 'user' , 'store' , 'product.brand' , 'product.category'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'message' => 'Stock history retrieved successfully.',
            'data' => $history
        ]);
    }
}

