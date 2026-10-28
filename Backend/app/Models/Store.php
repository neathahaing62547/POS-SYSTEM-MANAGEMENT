<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

class Store extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'address',
        'status',
    ];

    public function inventoryStocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
    public function stockHistories()
{
    return $this->hasMany(StockHistory::class);
}   
public function cashRegisters(): HasMany
{
    return $this->hasMany(CashRegister::class);
}
}
