<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsageRecord extends Model
{
    protected $table = 'usages';

    protected $fillable = [
        'sparepart_id', 'fleet_id', 'quantity', 'usage_date', 'notes', 'recorded_by',
    ];

    public function sparepart()
    {
        return $this->belongsTo(Sparepart::class);
    }

    public function fleet()
    {
        return $this->belongsTo(Fleet::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
