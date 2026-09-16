<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturnItem extends Model
{
    protected $fillable = [
        'return_id',
        'product_id',
        'quantity',
        'refund_price',
    ];

    public function return()
    {
        return $this->belongsTo(SalesReturn::class, 'return_id');
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
