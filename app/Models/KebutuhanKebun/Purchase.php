<?php

namespace App\Models\KebutuhanKebun;

use App\Models\Garden;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $table = 'kebun_purchases';

    protected $fillable = [
        'invoice_number', 'preorder_id', 'item_id', 'supplier_id', 'garden_id', 'target_garden_id',
        'quantity', 'unit_price', 'total_price', 'purchase_date', 'recorded_by',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function preorder()
    {
        return $this->belongsTo(Preorder::class, 'preorder_id');
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