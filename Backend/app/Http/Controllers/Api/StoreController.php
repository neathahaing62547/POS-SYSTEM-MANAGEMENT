<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index()
    {
        $stores = Store::with(['inventoryStocks.product' , 'purchases'])
         ->latest()->paginate();

        return response()->json([
            'message' => 'Stores retrieved successfully',
            'total' => $stores->count(),
            'data' => $stores
        ]);
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $store = Store::create($data);

        return response()->json([
            'message' => 'Store created successfully',
            'data' => $store
        ], 201);
    }
    public function show(Store $store)
    {
        return response()->json([
            'message' => 'Store retrieved successfully',
            'data' => $store
        ]);
    }
    public function update(Request $request, Store $store)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',         
        ]);
        $store->update($data);

        return response()->json([
            'message' => 'Store updated successfully',
            'data' => $store
        ]);
    }
    public function updateStatus(Request $request, Store $store)
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);
        $store->update([
            'status' => $data['status'],
        ]);
        return response()->json([
            'message' => 'Store status updated successfully.',
            'data' => $store,
        ]);
    }
    public function destroy(Store $store)
    {
        if ($store->inventoryStocks()->exists()) {
            return response()->json([
                'message' => 'Cannot delete store because it has inventory records. Deactivate it instead.'
            ], 422);
        }
        if ($store->purchases()->exists()) {
            return response()->json([
                'message' => 'Cannot delete store because it has purchase history. Deactivate it instead.'
            ], 422);
        }
        $store->delete();

        return response()->json([
            'message' => 'Store deleted successfully'
        ]);
    }
}
