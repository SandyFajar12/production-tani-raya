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

    public function gardenStocks()
    {
        return $this->hasMany(GardenStock::class);
    }

    /** Baris untuk modal Detail: stok dipisah per kebun, lalu total */
    public function stockDetail(): array
    {
        $unit = $this->unit->name ?? '';
        $rows = ['## Stok per Kebun' => ''];

        foreach ($this->gardenStocks->sortBy(function ($g) { return $g->garden->name ?? ''; }) as $g) {
            $rows[$g->garden->name ?? '-'] = $g->quantity.' '.$unit;
        }

        $rows['Total Stok'] = $this->current_stock.' '.$unit;

        return $rows;
    }

    /** Kebun yang stok sparepart ini sudah di bawah atau sama dengan stok minimum */
    public function lowStockGardens()
    {
        return $this->gardenStocks->filter(fn ($gs) => $gs->quantity <= $this->min_stock);
    }

    public function isLowStock(): bool
    {
        return $this->lowStockGardens()->isNotEmpty();
    }
}
