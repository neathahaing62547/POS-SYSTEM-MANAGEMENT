<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryStock;

class CheckoutController extends Controller
{
    public function checkout()
    {
        $cart = Cart::with(['items.product'])
            ->where('user_id', Auth::id())
            ->first();
        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty'
            ], 400);
        }
        $items = [];
        $subtotal = 0;
        foreach ($cart->items as $item) {
            $totalPrice = $item->price * $item->quantity;
            $subtotal += $totalPrice;

            $items[] = [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'quantity' => $item->quantity,
                'unit_price' => $item->price,
                'total_price' => $totalPrice,
            ];
        }
        $discount = 0;
        $tax = $subtotal * 0.10;
        $grandTotal = $subtotal - $discount + $tax;
        return response()->json([
            'message' => 'Checkout data',
            'data' => [
                'items' => $items,
                'subtotal' => round($subtotal, 2),
                'discount_amount' => round($discount, 2),
                'tax_amount' => round($tax, 2),
                'grand_total' => round($grandTotal, 2),
            ]
        ]);
    }
    public function confirm(Request $request)
    {
        $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
        ]);

        return DB::transaction(function () use ($request) {
            $cart = Cart::with(['items.product'])
                ->where('user_id', Auth::id())
                ->first();
            if (!$cart || $cart->items->isEmpty()) {
                return response()->json([
                    'message' => 'Cart is empty'
                ], 400);
            }
            foreach ($cart->items as $item) {

                $product = $item->product;

                if (!$product) {
                    return response()->json([
                        'message' => 'Product not found',
                        'product_id' => $item->product_id
                    ], 404);
                }
                $stock = InventoryStock::where('product_id', $item->product_id)
                    ->where('store_id', 1)
                    ->first();
                if (!$stock || $stock->quantity < $item->quantity) {
                    return response()->json([
                        'message' => 'Not enough stock',
                        'product' => $product->name,
                        'available_stock' => $stock?->quantity ?? 0,
                        'requested_quantity' => $item->quantity,
                    ], 422);
                }
            }
            $subtotal = 0;
            foreach ($cart->items as $item) {
                $subtotal += $item->price * $item->quantity;
            }
            $discount = 0;
            $tax = $subtotal * 0.10;
            $grandTotal = $subtotal - $discount + $tax;

            $paymentMethod = PaymentMethod::findOrFail(
                $request->payment_method_id
            );
            $methodName = strtolower($paymentMethod->name);
            if ($methodName === 'cash') {

                $order = Order::create([
                    'invoice_no' => 'AHRS-' . time(),
                    'cashier_id' => Auth::id(),
                    'shift_id' => null,
                    'sub_total' => $subtotal,
                    'discount_amount' => $discount,
                    'tax_amount' => $tax,
                    'grand_total' => $grandTotal,
                    'status' => 'paid',
                ]);
                foreach ($cart->items as $item) {

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->price,
                        'subtotal' => $item->price * $item->quantity,
                    ]);
                    $stock = InventoryStock::where('product_id', $item->product_id)
                        ->where('store_id', 1)
                        ->first();
                    $stock->quantity -= $item->quantity;
                    $stock->save();
                }
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'payment_method_id' => $paymentMethod->id,
                    'amount' => $grandTotal,
                    'status' => 'paid',
                    'transaction_id' => 'PAY-' . time(),
                    'payment_data' => null,
                    'paid_at' => now(),
                ]);
                $cart->items()->delete();
                return response()->json([
                    'message' => 'Cash payment successful',
                    'data' => [
                        'order' => $order,
                        'items' => $order->items,
                        'payment' => $payment,
                    ]
                ], 201);
            }
            if ($methodName === 'aba') {
                $order = Order::create([
                    'invoice_no' => 'AHRS-' . time(),
                    'cashier_id' => Auth::id(),
                    'shift_id' => null,
                    'sub_total' => $subtotal,
                    'discount_amount' => $discount,
                    'tax_amount' => $tax,
                    'grand_total' => $grandTotal,
                    'status' => 'pending',
                ]);
                foreach ($cart->items as $item) {

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->price,
                        'subtotal' => $item->price * $item->quantity,
                    ]);
                }
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'payment_method_id' => $paymentMethod->id,
                    'amount' => $grandTotal,
                    'status' => 'pending',
                    'transaction_id' => 'PAY-' . time(),
                    'payment_data' => null,
                    'paid_at' => null,
                ]);
                return response()->json([
                    'message' => 'ABA payment created',
                    'data' => [
                        'order' => $order,
                        'items' => $order->items,
                        'payment' => $payment,
                        'amount' => round($grandTotal, 2),
                        'next_step' => 'Generate ABA QR',
                    ]
                ], 201);
            }
            return response()->json([
                'message' => 'Unsupported payment method',
                'payment_method' => $paymentMethod->name,
            ], 422);
        });
    }
    public function paymentSuccess($paymentId)
    {
        try {
            return DB::transaction(function () use ($paymentId) {

                $payment = Payment::with([
                    'order.items.product'
                ])->findOrFail($paymentId);
                if ($payment->status === 'paid') {
                    return response()->json([
                        'message' => 'Payment already completed'
                    ], 400);
                }
                $order = $payment->order;
                foreach ($order->items as $item) {

                    $product = $item->product;

                    if (!$product) {
                        return response()->json([
                            'message' => 'Product not found',
                            'product_id' => $item->product_id
                        ], 404);
                    }
                }
                foreach ($order->items as $item) {
                    $stock = InventoryStock::where('product_id', $item->product_id)
                        ->where('store_id', 1)
                        ->lockForUpdate()
                        ->first();
                    if (!$stock || $stock->quantity < $item->quantity) {
                        return response()->json([
                            'message' => 'Not enough stock',
                            'product_id' => $item->product_id,
                            'available_stock' => $stock?->quantity ?? 0,
                            'requested_quantity' => $item->quantity,
                        ], 422);
                    }
                    $stock->quantity -= $item->quantity;
                    $stock->save();
                }
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
                $order->update([
                    'status' => 'paid',
                ]);
                $cart = Cart::where('user_id', Auth::id())->first();
                if ($cart) {
                    $cart->items()->delete();
                }
                return response()->json([
                    'message' => 'ABA payment successful',
                    'data' => [
                        'order' => $order,
                        'payment' => $payment,
                    ]
                ], 200);
            });
        } catch (\Exception $eror) {
            return response()->json([
                'message' =>  'what wrong with ' . $eror
            ]);
        }
    }
}
