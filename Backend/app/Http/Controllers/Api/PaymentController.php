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
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%")
                    ->orWhere('payment_method_id', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }
        $amountpaymentaba = Payment::whereHas('paymentMethod' , function ($q){
              $q->where('name' ,'ABA');
        })->count();
       $cashpayment = Payment::whereHas('paymentMethod' , function ($q){
              $q->where('name' ,'Cash');
        })->count();
        $amountpayment = Payment::count();
        $totalpayment = Payment::sum('amount');
        $payment = $query->latest()->paginate(30);
        $payment->load(   'order.cashier:id,username', 'paymentMethod');
        return response()->json([
            'Message' => 'Payment  retrieved successfully',
            'summary' => [
                'CASH PAY' => $cashpayment,
                'ABA PAY ' => $amountpaymentaba,
                'Total PAyment' => $totalpayment,
                'Amount Payment' => $amountpayment,
            ],
            'data' => [
                'data' => $payment,
            ]
        ]);
    }
}
