<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use Illuminate\Support\Facades\Auth;
use  App\Models\InventoryStock;
use App\Models\Product;
use App\Models\OrderItem;

class SalesReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesReturn::query();
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('total_refunded', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }
        $summary = [
            'total_returns' => SalesReturn::count(),
            'total_refunded' => SalesReturn::sum('total_refunded'),
            'completed_returns' => SalesReturn::where('status', 'completed')->count(),
            'total_returned_quantity' => SalesReturnItem::sum('quantity'),
        ];

        $salereturn = SalesReturn::with('items','user:id,username')
            ->latest()
            ->paginate(10);
        return response()->json([
            'message' => 'sale return received successfully',
            'summary' => $summary,
            'data' => $salereturn
        ]);
    }
    public function show(SalesReturn $salesReturn)
    {
        $salesReturn->load([
            'items.product',
            'order',
           'user:id,username'
        ]);
        return response()->json([
            'message' => 'Sales return details retrieved successfully',
            'data' => $salesReturn
        ]);
    }
    public function showorder(Order $order)
    {
        $order->load('items.product');
        return response()->json([
            'data' => $order
        ]);
    }
    public  function store(Request $request, Order $order)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string',
        ]);
        $order->load('items');
        $totalRefunded = 0;
        $salesReturn = SalesReturn::create([
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'total_refunded' => 0,
            'reason' => $request->reason,
            'status' => 'completed',
        ]);
        foreach ($request->items as $item) {

            $orderItem =  $order->items
                ->where('product_id', $item['product_id'])
                ->first();
            if (!$orderItem) {
                abort(422, 'Product not found in this order');
            }
            if ($item['quantity']  > $orderItem->quantity) {

                abort(422, 'Return quantity cannot be greater than sold quantity');
            }
            $refund = $item['quantity'] * $orderItem->unit_price;
            $totalRefunded += $refund;
            $salesReturn->items()->create([
                'product_id' => $orderItem->product_id,
                'quantity' => $item['quantity'],
                'refund_price' => $orderItem->unit_price,
            ]);

            $stock = InventoryStock::where(
                'product_id',
                $orderItem->product_id
            )->first();

            if ($stock) {
                $stock->increment('quantity', $item['quantity']);
            }
            $salesReturn->update([
                'total_refunded' => $totalRefunded,
            ]);
        }

        return response()->json([
            'message' => 'Return created successfully',
            'data' => $salesReturn->load('items.product'),
        ], 201);
    }
}
