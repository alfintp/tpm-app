<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Stock extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'code',
        'category',
        'name',
        'quantity',
        'unit',
        'limit_qty',
        'is_active',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'limit_qty' => 'integer',
        'is_active' => 'boolean',
    ];

    public function usages()
    {
        return $this->hasMany(StockUsage::class, 'stock_id');
    }

    public function getIsLowStockAttribute()
    {
        return $this->quantity <= $this->limit_qty;
    }
}
