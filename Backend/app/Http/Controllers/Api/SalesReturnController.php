<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use Illuminate\Support\Facades\Auth;
use  App\Models\InventoryStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\PosStoreResolver;

class SalesReturnController extends Controller
{
    public function __construct(private PosStoreResolver $posStoreResolver) {}

    public function index(Request $request)
    {
        $query = SalesReturn::query();
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('total_refunded', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('order', function ($orderQuery) use ($search) {
                        $orderQuery->where('invoice_no', 'like', "%{$search}%");
                    });
            });
        }
        $summary = [
            'total_returns' => SalesReturn::count(),
            'total_refunded' => SalesReturn::sum('total_refunded'),
            'completed_returns' => SalesReturn::where('status', 'completed')->count(),
            'total_returned_quantity' => SalesReturnItem::sum('quantity'),
        ];

        $salereturn = $query
            ->with(['items.product', 'order:id,invoice_no,grand_total,status', 'user:id,username'])
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
            'order:id,invoice_no,grand_total,status',
            'user:id,username',
        ]);
        return response()->json([
            'message' => 'Sales return details retrieved successfully',
            'data' => $salesReturn
        ]);
    }
    public function showorder(Order $order)
    {
        $order->load([
            'items.product',
            'salesReturns.items',
        ]);
        return response()->json([
            'data' => $order
        ]);
    }
    public function store(Request $request, Order $order)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|distinct',
            'items.*.quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:2000',
        ]);

        $salesReturn = DB::transaction(function () use ($request, $order) {
            $order = Order::whereKey($order->id)
                ->lockForUpdate()
                ->with(['items', 'shift.cashRegister'])
                ->firstOrFail();
            if (!in_array($order->status, ['paid', 'completed'], true)) {
                throw ValidationException::withMessages([
                    'order' => 'Only paid or completed orders can be returned.',
                ]);
            }
            $storeId = $order->shift
                ? $this->posStoreResolver->storeIdForShift($order->shift)
                : null;
            if (!$storeId) {
                throw ValidationException::withMessages([
                    'order' => 'The order is not linked to a store shift, so stock cannot be returned safely.',
                ]);
            }
            $validatedItems = [];
            $totalRefunded = 0;
            foreach ($request->items as $item) {
                $orderItem = $order->items
                    ->firstWhere('product_id', $item['product_id']);

                if (!$orderItem) {
                    throw ValidationException::withMessages([
                        'items' => 'A selected product was not sold in this order.',
                    ]);
                }
                $previouslyReturned = SalesReturnItem::whereIn(
                    'return_id',
                    SalesReturn::where('order_id', $order->id)->select('id')
                )
                    ->where('product_id', $orderItem->product_id)
                    ->sum('quantity');
                $returnableQuantity = $orderItem->quantity - $previouslyReturned;

                if ($item['quantity'] > $returnableQuantity) {
                    throw ValidationException::withMessages([
                        'items' => "Only {$returnableQuantity} more unit(s) of {$orderItem->product_name} can be returned.",
                    ]);
                }
                $stock = InventoryStock::where('product_id', $orderItem->product_id)
                    ->where('store_id', $storeId)
                    ->lockForUpdate()
                    ->first();

                if (!$stock) {
                    throw ValidationException::withMessages([
                        'items' => "Store stock is unavailable for {$orderItem->product_name}.",
                    ]);
                }
                $stock->increment('quantity', $item['quantity']);
                $totalRefunded += $item['quantity'] * $orderItem->unit_price;
                $validatedItems[] = [
                    'product_id' => $orderItem->product_id,
                    'quantity' => $item['quantity'],
                    'refund_price' => $orderItem->unit_price,
                ];
            }

            $salesReturn = SalesReturn::create([
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'total_refunded' => $totalRefunded,
                'reason' => $request->reason,
                'status' => 'completed',
            ]);

            $salesReturn->items()->createMany($validatedItems);
            return $salesReturn;
        });

        return response()->json([
            'message' => 'Return recorded successfully',
            'data' => $salesReturn->load(['items.product', 'order:id,invoice_no,grand_total,status', 'user:id,username']),
        ], 201);
    }
}
