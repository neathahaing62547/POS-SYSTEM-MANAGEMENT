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

class PosCartController extends Controller
{
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
        $cart = Cart::firstOrCreate([
            'user_id' => Auth::id()
        ]);
        $product = Product::where('id', $request->product_id)
            ->where('status', 'active')
            ->firstOrFail();

        $stock = InventoryStock::where('product_id', $product->id)
            ->where('store_id', 1)
            ->first();

        if (!$stock) {
            return response()->json([
                'message' => 'Stock record not found',
                'product' => $product->name,
            ], 404);
        }

        $cartitems = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        $currentQuantity = $cartitems ? $cartitems->quantity : 0;

        if ($currentQuantity + $request->quantity > $stock->quantity) {
            return response()->json([
                'message' => 'Not enough stock',
                'product' => $product->name,
                'available_stock' => $stock->quantity,
                'cart_quantity' => $currentQuantity,
            ], 422);
        }
        if ($cartitems) {
           $cartitems->increment('quantity', $request->quantity);
        } else {
            $cartitems =  CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => $product->sale_price,
            ]);
        }
        return response()->json([
            'message' => 'Product added to cart successfully.',
            'data' => $cartitems
        ]);
    }
    public function removeitem($remove)
    {
        $item = CartItem::findOrFail($remove);
        if (!$item) {
            return response()->json([
                'message' =>  'cart item remove NOt found ',
            ]);
        }
        $item->delete();
        return response()->json([
            'message ' => 'item remove ',
        ]);
    }
    public function increase(Request $request, $cartitems)
    {
        $item  = CartItem::findOrFail($cartitems);
        $item->increment('quantity');
        return response()->json([
            'message' => 'Quantity increased successfully',
            'data' => ['id' => $item->id, 'quantity' => $item->quantity,]
        ]);
    }
    public function decrease(Request $request, $cartitems)
    {
        $item  = CartItem::findOrFail($cartitems);
        if ($item->quantity > 1) {
            $item->decrement('quantity');
        }
        return response()->json([
            'message' => 'Quantity increased successfully',
            'data' => [
                'id' => $item->id,
                'quantity' => $item->quantity,]
        ]);
    }
}
