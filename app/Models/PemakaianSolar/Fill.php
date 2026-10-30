<?php

namespace App\Models\PemakaianSolar;

use App\Models\Fleet;
use App\Models\Garden;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Fill extends Model
{
    protected $table = 'solar_fills';

    protected $fillable = [
        'fleet_id', 'trip_id', 'garden_id', 'fill_date', 'quantity', 'balance_before', 'balance_after', 'notes', 'recorded_by',
    ];

    public function fleet()
    {
        return $this->belongsTo(Fleet::class);
    }

    /** Kebun sumber solar */
    public function garden()
    {
        return $this->belongsTo(Garden::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}