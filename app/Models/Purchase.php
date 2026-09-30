<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'invoice_number', 'preorder_id', 'sparepart_id', 'supplier_id',
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
}
