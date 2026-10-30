<?php

namespace App\Models\KebutuhanKebun;

use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'kebun_items';

    protected $fillable = [
        'code', 'name', 'category_id', 'unit_id',
        'min_stock', 'current_stock', 'standard_price', 'main_supplier_id', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function mainSupplier()
    {
        return $this->belongsTo(Supplier::class, 'main_supplier_id');
    }

    public function gardenStocks()
    {
        return $this->hasMany(GardenStock::class, 'item_id');
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
}