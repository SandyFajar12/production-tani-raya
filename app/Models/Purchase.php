<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'invoice_number', 'preorder_id', 'sparepart_id', 'supplier_id', 'garden_id', 'target_garden_id',
        'quantity', 'unit_price', 'total_price', 'purchase_date', 'recorded_by',
    ];

    public function sparepart()
    {
        return $this->belongsTo(Sparepart::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function preorder()
    {
        return $this->belongsTo(Preorder::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** Stok fisik masuk ke kebun ini ("Diterima di") */
    public function receivedGarden()
    {
        return $this->belongsTo(Garden::class, 'garden_id');
    }

    /** Pembelian ini ditujukan untuk kebun ini ("Kebun Tujuan") */
    public function targetGarden()
    {
        return $this->belongsTo(Garden::class, 'target_garden_id');
    }
}