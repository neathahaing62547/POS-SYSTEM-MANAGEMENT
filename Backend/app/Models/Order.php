<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'invoice_no',
        'cashier_id',
        'shift_id',
        'sub_total',
        'discount_amount',
        'tax_amount',
        'grand_total',
        'status',
    ];

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function payment()
    {
        return $this->hasOne(Payment::class, 'order_id');
    }
    public function salesReturns()
    {
        return $this->hasMany(SalesReturn::class);
    }
}
