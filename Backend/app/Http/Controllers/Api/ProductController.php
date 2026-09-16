<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\InventoryStock;
use Illuminate\Http\Request;
use  App\Models\Product;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'brand'])
            ->withSum('inventoryStocks', 'quantity');

        $totalproduct = Product::count();

        $statusactive = Product::where('status', 'active')->count();

        $statusinactive = Product::where('status', 'inactive')->count();

        $alert_stock = InventoryStock::whereColumn(
            'quantity',
            '<=',
            'alert_quantity'
        )->count();

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('cost_price', 'like', "%{$search}%")
                    ->orWhere('sale_price', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('brand', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        if ($request->filled('stock')) {
            if ($request->stock === 'low') {
                $query->having('inventory_stocks_sum_quantity', '<=', 10);
            }
            if ($request->stock === 'in') {
                $query->having('inventory_stocks_sum_quantity', '>', 10);
            }
        }

        $products = $query
            ->latest('id')
            ->paginate(50);

        return response()->json([
            'Message' => 'Products retrieved successfully',

            'summary' => [
                'total' => $totalproduct,
                'active' => $statusactive,
                'inactive' => $statusinactive,
                'stock alert' => $alert_stock
            ],

            'data' => $products
        ]);
    }
    public function store(Request $request)
    {
        $validated =  $request->validate([
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'barcode' => 'nullable|string|max:255|unique:products,barcode',
            'sku' => 'nullable|string|max:255|unique:products,sku',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'image' => 'nullable|max:2048|mimes:jpg,jpeg,png,webp',
            'status' => 'nullable|in:active,inactive',
        ]);
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')
                ->store('product', 'public');
        }
        $product = Product::create([
            'category_id' => $validated['category_id'],
            'brand_id' => $validated['brand_id'],
            'barcode' => $validated['barcode'],
            'sku' => $validated['sku'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'cost_price' => $validated['cost_price'],
            'sale_price' => $validated['sale_price'],
            'unit' => $validated['unit'],
            'image' => $imagePath,
            'status' => $validated['status'] ?? 'active',
        ]);
        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product
        ], 201);
    }
    public function show($product)
    {
        $products = Product::with(['category', 'brand'])
            ->withSum('inventoryStocks', 'quantity')
            ->findOrFail($product);
        return response()->json([
            'Message' => 'Products retrieved successfully',
            'data' => $products
        ]);
    }
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'sku' => [
                'required',
                Rule::unique('products', 'sku')->ignore($product->id),
            ],
            'barcode' => [
                'nullable',
                Rule::unique('products', 'barcode')->ignore($product->id),
            ],
            'name' => 'required|string|max:255',
            'cost_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'status' => 'nullable|in:active,inactive',
            'image' => 'nullable|max:2048|mimes:jpg,jpeg,png,webp',
        ]);
        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image']  = $product->image = $request->file('image')
                ->store('products', 'public');
        }
        $product->update($validated);
        $product->load(['category', 'brand']);
        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $product
        ]);
    }
    public function destroy($product)
    {
        $product = Product::find($product);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }
}
