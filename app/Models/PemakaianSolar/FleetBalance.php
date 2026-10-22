<?php

namespace App\Models\PemakaianSolar;

use App\Models\Fleet;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Model;

class FleetBalance extends Model
{
    protected $table = 'solar_fleet_balances';

    protected $fillable = ['fleet_id', 'balance', 'min_standby'];

    /**
     * Kabari Admin Pusat dan Owner kalau solar standby armada turun sampai di bawah batas minimum.
     * Hanya dikirim saat saldo turun (bukan tiap perjalanan), dan tidak aktif kalau minimum = 0.
     */
    public static function notifyIfLow(int $fleetId, float $before, float $after): void
    {
        $min = round((float) static::where('fleet_id', $fleetId)->value('min_standby'), 2);
        $before = round($before, 2);
        $after = round($after, 2);

        if ($min > 0 && $after < $min && $after < $before) {
            $fleet = Fleet::find($fleetId);

            if ($fleet) {
                NotificationService::lowFuelStandby($fleet, $after, $min);
            }
        }
    }

    public function fleet()
    {
        return $this->belongsTo(Fleet::class);
    }
}