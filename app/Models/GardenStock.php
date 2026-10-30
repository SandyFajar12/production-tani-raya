<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GardenStock extends Model
{
    protected $fillable = ['sparepart_id', 'garden_id', 'quantity'];

    public function sparepart()
    {
        return $this->belongsTo(Sparepart::class);
    }

    public function garden()
    {
        return $this->belongsTo(Garden::class);
    }
}