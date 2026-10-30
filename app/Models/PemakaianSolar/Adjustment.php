<?php

namespace App\Models\PemakaianSolar;

use App\Models\Garden;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Adjustment extends Model
{
    protected $table = 'solar_adjustments';

    protected $fillable = [
        'garden_id', 'to_garden_id', 'type', 'system_qty_before', 'physical_qty',
        'difference', 'reason', 'adjusted_by',
        'photo_path', 'latitude', 'longitude', 'location_label',
    ];

    public function garden()
    {
        return $this->belongsTo(Garden::class, 'garden_id');
    }

    public function toGarden()
    {
        return $this->belongsTo(Garden::class, 'to_garden_id');
    }

    public function adjuster()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}