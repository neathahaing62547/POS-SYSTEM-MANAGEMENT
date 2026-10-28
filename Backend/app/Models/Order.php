<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'order_id');
    }
    public function salesReturns(): HasMany
    {
        return $this->hasMany(SalesReturn::class);
    }
    public function shift(): BelongsTo
    {
        return $this->belongsTo(UserShift::class, 'shift_id');
    }
}
