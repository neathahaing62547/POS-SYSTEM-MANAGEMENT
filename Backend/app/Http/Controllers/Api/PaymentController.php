<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;


class PaymentController extends Controller
{
    public function  index(Request $request)
    {
        $query = Payment::query();
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }
        $abaPayments = Payment::whereRaw('LOWER(payment_method) = ?', ['aba'])->count();
        $cashPayments = Payment::whereRaw('LOWER(payment_method) = ?', ['cash'])->count();
        $paymentCount = Payment::count();
        $totalAmount = Payment::sum('amount');
        $payment = $query
            ->with(['order:id,invoice_no,cashier_id', 'order.cashier:id,username'])
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Payments retrieved successfully',
            'summary' => [
                'cash_payments' => $cashPayments,
                'aba_payments' => $abaPayments,
                'pending_payments' => Payment::where('status', 'pending')->count(),
                'total_amount' => $totalAmount,
                'payment_count' => $paymentCount,
            ],
            'data' => $payment,
        ]);
    }
}
