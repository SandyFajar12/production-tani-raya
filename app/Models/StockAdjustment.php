<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    protected $fillable = [
        'sparepart_id', 'type', 'system_qty_before', 'physical_qty',
        'difference', 'reason', 'adjusted_by',
        'photo_path', 'latitude', 'longitude', 'location_label',
    ];

    public function sparepart()
    {
        return $this->belongsTo(Sparepart::class);
    }

    public function adjuster()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}
