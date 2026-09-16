<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $table = 'purchase_order_items';
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'quantity',
        'cost_price',
        'subtotal',
    ];
    public function purchase()
    {
        return $this->belongsTo(
            Purchase::class,
            'purchase_order_id'
        );
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function store()
{
    return $this->belongsTo(Store::class);
}
}