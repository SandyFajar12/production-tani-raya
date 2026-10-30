<?php

namespace App\Http\Controllers\PemakaianSolar;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $summary = [
            'purchased_today' => \App\Models\PemakaianSolar\Trip::whereDate('trip_date', $today)->where('status', 'done')->sum('purchased_qty'),
            'used_today' => \App\Models\PemakaianSolar\Trip::whereDate('trip_date', $today)->where('status', 'done')->sum('used_qty'),
            'low_stock_count' => \App\Models\PemakaianSolar\Stock::join('gardens', 'gardens.id', '=', 'solar_stocks.garden_id')
                ->where('gardens.is_active', true)
                ->where('solar_stocks.min_stock', '>', 0)
                ->whereColumn('solar_stocks.quantity', '<=', 'solar_stocks.min_stock')
                ->count(),
            'low_standby_count' => \App\Models\PemakaianSolar\FleetBalance::join('fleets', 'fleets.id', '=', 'solar_fleet_balances.fleet_id')
                ->where('fleets.status', '!=', 'nonaktif')
                ->where('solar_fleet_balances.min_standby', '>', 0)
                ->whereColumn('solar_fleet_balances.balance', '<', 'solar_fleet_balances.min_standby')
                ->count(),
            'transactions_today' => \App\Models\PemakaianSolar\Trip::whereDate('trip_date', $today)->where('status', '!=', 'cancelled')->count()
                + \App\Models\PemakaianSolar\Fill::whereDate('fill_date', $today)->count()
                + \App\Models\PemakaianSolar\Adjustment::whereDate('created_at', $today)->count()
                + \App\Models\PemakaianSolar\BalanceAdjustment::whereDate('created_at', $today)->count(),
        ];

        return view('pemakaian-solar.dashboard', compact('summary'));
    }
}