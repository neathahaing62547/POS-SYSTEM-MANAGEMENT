<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryStock;
use App\Services\PosStoreResolver;

class PosCartController extends Controller
{
    public function __construct(private PosStoreResolver $posStoreResolver)
    {
    }
    public function index(Request $request)
    {
        $cart = Cart::firstOrCreate([
            'user_id' => Auth::id()
        ]);
        $cart->load([
            'items.product'
        ]);
        $amount = $cart->items()->count();

        $qty = $cart->items()->sum('quantity');

        $total = $cart->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        return response()->json([
            'message' =>  'Show Current Cart',
            'amount' =>  $amount,
            'Quantity' => $qty,
            'total' =>  $total,
            'data' => $cart
        ]);
    }
    public function Addtocarts(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);
        return DB::transaction(function () use ($request) {
            $cart = Cart::firstOrCreate([
                'user_id' => Auth::id()
            ]);
            $product = Product::where('id', $request->product_id)
                ->where('status', 'active')
                ->firstOrFail();
            $storeId = $this->posStoreResolver->storeIdForUser(Auth::id());

            $stock = InventoryStock::where('product_id', $product->id)
                ->where('store_id', $storeId)
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                return response()->json([
                    'message' => 'Stock record not found',
                    'product' => $product->name,
                ], 404);
            }
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();
            $currentQuantity = $cartItem ? $cartItem->quantity : 0;
            if ($currentQuantity + $request->quantity > $stock->quantity) {
                return response()->json([
                    'message' => 'Not enough stock',
                    'product' => $product->name,
                    'available_stock' => $stock->quantity,
                    'cart_quantity' => $currentQuantity,
                ], 422);
            }
            if ($cartItem) {
                $cartItem->increment('quantity', $request->quantity);
            } else {
                $cartItem = CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => $request->quantity,
                    'price' => $product->sale_price,
                ]);
            }
            return response()->json([
                'message' => 'Product added to cart successfully.',
                'data' => $cartItem
            ]);
        });
    }
    public function removeitem($remove)
    {
        $item = $this->currentUserCartItem($remove);
        $item->delete();
        return response()->json([
            'message' => 'Cart item removed successfully.',
        ]);
    }
    public function increase(Request $request, $cartitems)
    {
        return DB::transaction(function () use ($cartitems) {
            $item = $this->currentUserCartItem($cartitems, true);
            $storeId = $this->posStoreResolver->storeIdForUser(Auth::id());
            $stock = InventoryStock::where('product_id', $item->product_id)
                ->where('store_id', $storeId)
                ->lockForUpdate()
                ->first();

            if (!$stock || $item->quantity >= $stock->quantity) {
                return response()->json([
                    'message' => 'Not enough stock',
                    'available_stock' => $stock?->quantity ?? 0,
                    'cart_quantity' => $item->quantity,
                ], 422);
            }
            $item->increment('quantity');
            return response()->json([
                'message' => 'Quantity increased successfully',
                'data' => ['id' => $item->id, 'quantity' => $item->quantity]
            ]);
        });
    }
    public function decrease(Request $request, $cartitems)
    {
        $item = $this->currentUserCartItem($cartitems);
        if ($item->quantity > 1) $item->decrement('quantity');
        return response()->json([
            'message' => 'Quantity decreased successfully',
            'data' => ['id' => $item->id, 'quantity' => $item->quantity]
        ]);
    }
    private function currentUserCartItem($itemId, bool $lock = false): CartItem
    {
        $query = CartItem::whereHas('cart', function ($cartQuery) {
            $cartQuery->where('user_id', Auth::id());
        });
        if ($lock) $query->lockForUpdate();
        return $query->findOrFail($itemId);
    }
}
