<?php

namespace App\Http\Controllers\Api;

use App\Models\Purchase;
use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\Event\TestSuite\Loaded;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::with([
            'supplier',
            'items.product.brand',
            'items.product.category'
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($supplier) use ($search) {
                        $supplier->where('company_name', 'like', "%{$search}%")
                            ->orWhere('contact_person', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('items.product', function ($product) use ($search) {
                        $product->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%");
                    });
            });
        }
        $purchases = $query
            ->latest()
            ->paginate(25);
        $purchases->getCollection()->transform(function ($purchase) {
            return [
                'id' => $purchase->id,
                'po_number' => $purchase->po_number,
                'supplier' => $purchase->supplier,
                'status' => $purchase->status,
                'total_items' => $purchase->items->count(),
                'total_quantity' => $purchase->items->sum('quantity'),
                'total_cost' => $purchase->total_cost,
                'ordered_at' => $purchase->ordered_at,
                'items' => $purchase->items,
            ];
        });

        return response()->json([
            'message' => 'Purchases retrieved successfully',
            'data' => $purchases
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'ordered_at' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
        ]);

        $purchase = DB::transaction(function () use ($validated) {
            $total_cost = 0;
            $purchase = Purchase::create([
                'store_id' => $validated['store_id'],
                'supplier_id' => $validated['supplier_id'],
                'po_number' => 'PO-' . time(),
                'total_cost' => 0,
                'status' => 'pending',
                'ordered_at' => $validated['ordered_at'],
            ]);
            foreach ($validated['items'] as $item) {
                $subtotal =  $item['quantity'] * $item['cost_price'];

                $purchase->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'cost_price' => $item['cost_price'],
                    'subtotal' => $subtotal,
                ]);
                $total_cost += $subtotal;
            }
            $purchase->update([
                'total_cost' => $total_cost
            ]);
            return $purchase;
        });
        $purchase->load('supplier', 'items.product');
        return response()->json([
            'message' => 'Purchase created successfully',
            'data' => $purchase->load([
                'supplier',
                'items.product'
            ]),
        ], 201);
    }
    public function show(Request $request, Purchase $purchase)
    {
        $purchase->load([
            'supplier',
            'items.product.brand',
            'items.product.category'
        ]);
        return response()->json([
            'message' => 'Purchase created successfully',
            'data' =>  $purchase
        ]);
    }
    public function update(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'ordered_at' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
        ]);

        if (
            $purchase->status === 'received' &&
            $purchase->store_id != $validated['store_id']
        ) {
            return response()->json([
                'message' => 'Cannot change store after purchase has been received.'
            ], 422);
        }

        $purchase = DB::transaction(function () use ($validated, $purchase) {

            $purchase->update([
                'store_id' => $validated['store_id'],
                'supplier_id' => $validated['supplier_id'],
                'ordered_at' => $validated['ordered_at'],
            ]);

            $purchase->items()->delete();

            $total_cost = 0;

            foreach ($validated['items'] as $item) {

                $subtotal = $item['quantity'] * $item['cost_price'];

                $purchase->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'cost_price' => $item['cost_price'],
                    'subtotal' => $subtotal,
                ]);

                $total_cost += $subtotal;
            }
            $purchase->update([
                'total_cost' => $total_cost,
            ]);

            return $purchase;
        });

        $purchase->load([
            'store',
            'supplier',
            'items.product.brand',
            'items.product.category',
        ]);

        return response()->json([
            'message' => 'Purchase updated successfully',
            'data' => $purchase,
        ]);
    }
    public function destroy(Purchase $purchase)
    {
        DB::transaction(function () use ($purchase) {
            $purchase->items()->delete();
            $purchase->delete();
        });
        return response()->json([
            'message' => 'Purchase deleted successfully',
        ]);
    }
    public function receive(Request $request, Purchase  $purchase)
    {
        try {
            DB::beginTransaction();

            $purchase->load('items.product');

            if ($purchase->status !== 'pending') {
                return response()->json([
                    'Message' => 'Purchase Con not Be Revice '
                ]);
            }

            foreach ($purchase->items as $item) {
                $inventory = InventoryStock::firstOrCreate(
                    [
                        'product_id' => $item->product_id,
                        'store_id' => $purchase->store_id,
                    ],
                    [
                        'quantity' => 0
                    ]
                );
                $inventory->quantity += $item->quantity;
                $inventory->save();

                StockHistory::create([
                    'product_id' => $item->product_id,
                    'store_id' => $purchase->store_id,
                    'user_id' => Auth::id(),
                    'type' => 'purchase',
                    'quantity_changed' => $item->quantity,
                    'reference_no' => $purchase->po_number,
                    'reason' => 'Purchase received',
                ]);
            }
            $purchase->update([
                'status' => 'received',
                'received_at' => now(),
            ]);
            DB::commit();
            return response()->json([
                'message' => 'Purchase received successfully.',
                'purchase' => $purchase->load('items')
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Cannot Recive the Purchase ' . $e,
            ]);
        }
    }
}
