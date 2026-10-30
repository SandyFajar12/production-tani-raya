<?php

namespace App\Models\KebutuhanKebun;

use App\Models\Garden;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Usage extends Model
{
    protected $table = 'kebun_usages';

    protected $fillable = [
        'item_id', 'garden_id', 'quantity', 'usage_date', 'notes', 'recorded_by',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function garden()
    {
        return $this->belongsTo(Garden::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}