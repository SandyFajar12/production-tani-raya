<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sparepart extends Model
{
    protected $fillable = [
        'code', 'name', 'category_id', 'dimension', 'unit_id',
        'min_stock', 'current_stock', 'standard_price', 'main_supplier_id', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(SparepartCategory::class, 'category_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function mainSupplier()
    {
        return $this->belongsTo(Supplier::class, 'main_supplier_id');
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class)->latest('id');
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->min_stock;
    }
}
