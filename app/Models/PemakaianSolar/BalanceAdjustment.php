<?php

namespace App\Models\PemakaianSolar;

use App\Models\Fleet;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BalanceAdjustment extends Model
{
    protected $table = 'solar_balance_adjustments';

    protected $fillable = [
        'fleet_id', 'balance_before', 'balance_after', 'difference', 'reason', 'adjusted_by',
    ];

    public function fleet()
    {
        return $this->belongsTo(Fleet::class);
    }

    public function adjuster()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}