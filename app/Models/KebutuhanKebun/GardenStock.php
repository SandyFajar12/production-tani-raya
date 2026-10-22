<?php

namespace App\Models\KebutuhanKebun;

use App\Models\Garden;
use Illuminate\Database\Eloquent\Model;

class GardenStock extends Model
{
    protected $table = 'kebun_garden_stocks';

    protected $fillable = ['item_id', 'garden_id', 'quantity'];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function garden()
    {
        return $this->belongsTo(Garden::class);
    }
}