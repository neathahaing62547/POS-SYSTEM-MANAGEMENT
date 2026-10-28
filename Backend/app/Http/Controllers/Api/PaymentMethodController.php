<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $methods = PaymentMethod::latest()->get();

        return response()->json([
            'message' => 'Payment methods retrieved successfully',
            'data' => $methods
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:payment_methods,code',
            'status' => 'nullable|in:active,inactive',
        ]);

        $method = PaymentMethod::create([
            'name' => $request->name,
            'code' => $request->code,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json([
            'message' => 'Payment method created successfully',
            'data' => $method
        ], 201);
    }
    public function show($id)
    {
        $method = PaymentMethod::findOrFail($id);

        return response()->json([
            'message' => 'Payment method retrieved successfully',
            'data' => $method
        ]);
    }
    public function update(Request $request, $id)
    {
        $method = PaymentMethod::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:payment_methods,code,' . $id,
            'status' => 'required|in:active,inactive',
        ]);
        $method->update([
            'name' => $request->name,
            'code' => $request->code,
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'Payment method updated successfully',
            'data' => $method
        ]);
    }
    public function destroy($id)
    {
        $method = PaymentMethod::findOrFail($id);

        if ($method->payments()->exists()) {
            return response()->json([
                'message' => 'This payment method has payment records and cannot be deleted. Set it to inactive instead.'
            ], 422);
        }

        $method->delete();

        return response()->json([
            'message' => 'Payment method deleted successfully'
        ]);
    }
    public function active()
    {
        $methods = PaymentMethod::where('status', 'active')->get();

        return response()->json([
            'message' => 'Active payment methods',
            'data' => $methods
        ]);
    }
}