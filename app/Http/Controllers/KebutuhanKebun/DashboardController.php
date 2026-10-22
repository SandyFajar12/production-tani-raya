<?php

namespace App\Http\Controllers\KebutuhanKebun;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $summary = [
            'purchase_today' => \App\Models\KebutuhanKebun\Purchase::whereDate('purchase_date', $today)->sum('total_price'),
            'usage_today' => \App\Models\KebutuhanKebun\Usage::whereDate('usage_date', $today)->sum('quantity'),
            'low_stock_count' => \App\Models\KebutuhanKebun\GardenStock::join('kebun_items', 'kebun_items.id', '=', 'kebun_garden_stocks.item_id')
                ->whereColumn('kebun_garden_stocks.quantity', '<=', 'kebun_items.min_stock')
                ->count(),
            'transactions_today' => \App\Models\KebutuhanKebun\Purchase::whereDate('purchase_date', $today)->count()
                + \App\Models\KebutuhanKebun\Usage::whereDate('usage_date', $today)->count()
                + \App\Models\KebutuhanKebun\Preorder::whereDate('created_at', $today)->count(),
            'preorder_pending' => \App\Models\KebutuhanKebun\Preorder::whereIn('status', PreorderController::waitingStatuses(auth()->user()))->count(),
        ];

        return view('kebutuhan-kebun.dashboard', compact('summary'));
    }
}