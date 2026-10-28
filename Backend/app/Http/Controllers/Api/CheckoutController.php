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
use  App\Models\UserShift;
use App\Services\PosStoreResolver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function __construct(private PosStoreResolver $posStoreResolver) {}

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
            'payment_method' => 'required|exists:payment_methods,id',
        ]);

        return DB::transaction(function () use ($request) {
            $shift = UserShift::with('cashRegister')
                ->where('user_id', Auth::id())
                ->where('status', 'open')
                ->first();

            if (!$shift) {
                return response()->json([
                    'message' => 'No open cashier shift. Please open a shift first.'
                ], 422);
            }
            $storeId = $this->posStoreResolver->storeIdForShift($shift);
            if (!$storeId) {
                return response()->json([
                    'message' => 'The open shift is not assigned to a store. Please contact an administrator.',
                ], 422);
            }
            $cart = Cart::with(['items.product'])
                ->where('user_id', Auth::id())
                ->first();
            if (!$cart || $cart->items->isEmpty()) {
                return response()->json([
                    'message' => 'Cart is empty'
                ], 400);
            }
            $paymentMethod = PaymentMethod::whereKey($request->payment_method)
                ->where('status', 'active')
                ->first();

            if (!$paymentMethod) {
                return response()->json([
                    'message' => 'This payment method is not active. Choose an active payment method and try again.',
                ], 422);
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
                    ->where('store_id', $storeId)
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

            $methodCode = strtolower(trim($paymentMethod->code));
            $methodName = strtolower(trim($paymentMethod->name));
            $isCash = $methodCode === 'cash' || $methodName === 'cash';
            if ($isCash) {
                $order = Order::create([
                    'invoice_no' => 'AHRS-' . time(),
                    'cashier_id' => Auth::id(),
                    'shift_id' => $shift->id,
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
                        'product_name' => $item->product->name,
                        'unit_price' => $item->price,
                        'cost_price' => $item->product->cost_price,
                        'discount_per_item' => 0,
                        'total_price' => $item->price * $item->quantity,
                    ]);
                    $stock = InventoryStock::where('product_id', $item->product_id)
                        ->where('store_id', $storeId)
                        ->first();

                    if (!$stock || $stock->quantity < $item->quantity) {
                        return response()->json([
                            'message' => 'Insufficient stock for ' . $item->product->name,
                        ], 422);
                    }

                    $stock->quantity -= $item->quantity;
                    $stock->save();
                }

                $payment = Payment::create([
                    'order_id' => $order->id,
                    'payment_method' => $paymentMethod->name,
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
                    ],
                ], 201);
            }
            $isAbaPayWay =
                $methodCode === 'aba_payway' ||
                $methodName === 'aba payway';
            if ($isAbaPayWay) {

                $order = Order::create([
                    'invoice_no' => 'AHRS-' . time(),
                    'cashier_id' => Auth::id(),
                    'shift_id' => $shift->id,
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
                        'product_name' => $item->product->name,
                        'unit_price' => $item->price,
                        'cost_price' => $item->product->cost_price,
                        'discount_per_item' => 0,
                        'total_price' => $item->price * $item->quantity,
                    ]);
                }

                $payment = Payment::create([
                    'order_id' => $order->id,
                    'payment_method' => $paymentMethod->name,
                    'amount' => $grandTotal,
                    'status' => 'pending',
                    'transaction_id' => null,
                    'payment_data' => null,
                    'paid_at' => null,
                ]);
                return response()->json([
                    'message' => 'Pending ABA PayWay payment created.',
                    'data' => [
                        'order_id' => $order->id,
                        'payment_id' => $payment->id,
                        'amount' => round($grandTotal, 2),
                        'currency' => config(
                            'services.aba_payway.currency',
                            'USD'
                        ),
                        'next_step' => 'Call POST /api/pos/aba-payway with this payment_id to generate the QR code.',
                    ],
                ], 201);
            }

            $order = Order::create([
                'invoice_no' => 'AHRS-' . time(),
                'cashier_id' => Auth::id(),
                'shift_id' => $shift->id,
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
                    'product_name' => $item->product->name,
                    'unit_price' => $item->price,
                    'cost_price' => $item->product->cost_price,
                    'discount_per_item' => 0,
                    'total_price' => $item->price * $item->quantity,
                ]);
            }

            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method' => $paymentMethod->name,
                'amount' => $grandTotal,
                'status' => 'pending',
                'transaction_id' => 'PAY-' . time(),
                'payment_data' => null,
                'paid_at' => null,
            ]);

            return response()->json([
                'message' => 'Payment created and is awaiting confirmation',
                'data' => [
                    'order' => $order,
                    'items' => $order->items,
                    'payment' => $payment,
                    'amount' => round($grandTotal, 2),
                    'next_step' => 'Confirm the payment with the selected payment provider before handing over the order.',
                ],
            ], 201);
        });
    }

    public function abaPayway(Request $request)
    {
        $request->validate([
            'payment_id' => ['required', 'integer'],
        ]);
        $payment = Payment::with('order.items.product')
            ->findOrFail($request->payment_id);
        if (
            !$payment->order ||
            (int) $payment->order->cashier_id !== (int) Auth::id()
        ) {
            return response()->json([
                'message' => 'Unauthorized payment.',
            ], 403);
        }
        if ($payment->status !== 'pending') {
            return response()->json([
                'message' => 'Payment is not pending.',
            ], 422);
        }

        $baseUrl = rtrim(
            config('services.aba_payway.base_url', ''),
            '/'
        );
        $merchantId = config('services.aba_payway.merchant_id');
        $publicKey = config('services.aba_payway.public_key');
        $currency = config('services.aba_payway.currency', 'USD');

        if (blank($merchantId) || blank($publicKey)) {
            return response()->json([
                'message' => 'ABA PayWay credentials are missing.',
            ], 500);
        }
        $tranId = $payment->transaction_id
            ?: 'P' . $payment->id . Str::upper(Str::random(6));

        $reqTime = now('UTC')->format('YmdHis');
        $amount = number_format((float) $payment->amount, 2, '.', '');

        $itemsArray = [];

        foreach ($payment->order->items as $item) {
            $itemsArray[] = [
                'name' => $item->product_name
                    ?? $item->product?->name
                    ?? 'POS Item',
                'quantity' => (int) $item->quantity,
                'price' => (float) $item->unit_price,
            ];
        }

        $items = base64_encode(
            json_encode($itemsArray, JSON_UNESCAPED_UNICODE)
        );

        $firstName = '';
        $lastName = '';
        $email = '';
        $phone = '';
        $purchaseType = 'purchase';
        $paymentOption = 'abapay_khqr';
        $callbackUrl = '';
        $returnDeeplink = '';
        
        $customFields = '';
        $returnParams = '';
        $payout = '';
        $lifetime = '15';
        $qrTemplate = 'template3_color';
        $hashString = $reqTime
            . $merchantId
            . $tranId
            . $amount
            . $items
            . $firstName
            . $lastName
            . $email
            . $phone
            . $purchaseType
            . $paymentOption
            . $callbackUrl
            . $returnDeeplink
            . $currency
            . $customFields
            . $returnParams
            . $payout
            . $lifetime
            . $qrTemplate;
        $hash = base64_encode(
            hash_hmac('sha512', $hashString, $publicKey, true)
        );
        $payload = [
            'req_time' => $reqTime,
            'merchant_id' => $merchantId,
            'tran_id' => $tranId,
            'amount' => $amount,
            'items' => $items,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'purchase_type' => $purchaseType,
            'payment_option' => $paymentOption,
            'callback_url' => null,
            'return_deeplink' => null,
            'currency' => $currency,
            'custom_fields' => null,
            'return_params' => null,
            'payout' => null,
            'lifetime' => (int) $lifetime,
            'qr_image_template' => $qrTemplate,
            'hash' => $hash,
        ];
        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->post(
                    $baseUrl . '/api/payment-gateway/v1/payments/generate-qr',
                    $payload
                );
            if (!$response->successful()) {
                return response()->json([
                    'message' => 'PayWay QR request failed.',
                    'provider_response' => $response->json(),
                ], 502);
            }
            $result = $response->json();

            if ((string) data_get($result, 'status.code') !== '0') {
                return response()->json([
                    'message' => 'PayWay did not generate the QR.',
                    'provider_response' => $result,
                ], 422);
            }
            $payment->update([
                'transaction_id' => $tranId,
                'payment_data' => json_encode([
                    'qrImage' => $result['qrImage'] ?? null,
                    'qrString' => $result['qrString'] ?? null,
                    'abapay_deeplink' =>
                    $result['abapay_deeplink'] ?? null,
                    'currency' => $currency,
                ]),
            ]);
            return response()->json([
                'message' => 'ABA PayWay QR generated successfully.',
                'data' => [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'transaction_id' => $tranId,
                    'amount' => $amount,
                    'currency' => $currency,
                    'qr_image' => $result['qrImage'] ?? null,
                    'qr_string' => $result['qrString'] ?? null,
                    'aba_deeplink' =>
                    $result['abapay_deeplink'] ?? null,
                    'payment_status' => 'pending',
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Could not connect to ABA PayWay Sandbox.',
            ], 502);
        }
    }
    public function paymentSuccess($paymentId)
    {
        try {
            return DB::transaction(function () use ($paymentId) {

                $payment = Payment::with([
                    'order.items.product',
                    'order.shift.cashRegister',
                ])->findOrFail($paymentId);
                if ($payment->status === 'paid') {
                    return response()->json([
                        'message' => 'Payment already completed'
                    ], 400);
                }
                $order = $payment->order;
                $storeId = $order?->shift
                    ? $this->posStoreResolver->storeIdForShift($order->shift)
                    : null;
                if (!$storeId) {
                    return response()->json([
                        'message' => 'The order is not linked to a store shift. Contact an administrator before confirming payment.',
                    ], 422);
                }
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
                        ->where('store_id', $storeId)
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
