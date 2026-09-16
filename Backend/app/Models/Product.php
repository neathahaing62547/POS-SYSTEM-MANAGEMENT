<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\Fluent\Concerns\Has;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'brand_id',
        'barcode',
        'sku',
        'name',
        'description',
        'cost_price',
        'sale_price',
        'unit',
        'image',
        'status',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    public function purchaseItems()
    {

        return $this->hasMany(purchaseItem::class);
    }
    public function inventoryStocks()
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function stockHistories()
    {
        return $this->hasMany(StockHistory::class);
    }
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function salesReturnItems()
    {
        return $this->hasMany(SalesReturnItem::class);
    }
}
