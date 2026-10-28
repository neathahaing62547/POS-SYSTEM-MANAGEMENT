<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
   protected $fillable = [
    'order_id',
    'payment_method',
    'amount',
    'status',
    'transaction_id',
    'payment_data',
    'paid_at',
];
    protected $casts = [
        'paid_at' => 'datetime',
        'payment_data' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
      public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}