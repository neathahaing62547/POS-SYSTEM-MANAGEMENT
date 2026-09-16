<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $table = 'purchase_orders';

    protected $fillable = [
        'store_id',
        'supplier_id',
        'po_number',
        'total_cost',
        'status',
        'ordered_at',
        'received_at',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    public function items()
    {
        return $this->hasMany(
            PurchaseItem::class,
            'purchase_order_id'
        );
    }
    public function store()
{
    return $this->belongsTo(Store::class);
}

}
