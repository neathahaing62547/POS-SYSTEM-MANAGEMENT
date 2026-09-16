<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with([
            'items.product',
            'payment.paymentMethod',
            'cashier:id,full_name'
        ])
            ->latest()
            ->get();
        $totalorder = Order::count();
        $pendingorder = Order::where('status', 'pending')->count();
        $paidorder = Order::where('status', 'paid')->count();
        $completedorder = Order::where('status', 'completed')->count();
        $cancelledorder = Order::where('status', 'cancelled')->count();

        return response()->json([
            'message' => 'Get all orders',
            'summary' => [
                'total' => $totalorder,
                'pending' => $pendingorder,
                'paid' => $paidorder,
                'completed' => $completedorder,
                'cancelled' => $cancelledorder
            ],
            'data' => $orders
        ]);
    }
    public function show($id)
    {
        $order = Order::with([
            'items.product',
            'payment.paymentMethod'
        ])->find($id);
        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }
        return response()->json([
            'message' => 'Get order detail',
            'data' => $order
        ]);
    }
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled'
        ]);
        $order = Order::find($id);
        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }
        $order->status = $request->status;
        $order->save();
        return response()->json([
            'message' => 'Order status updated successfully',
            'data' => $order
        ]);
    }
}
