<?php

namespace App\Http\Controllers;

use App\Models\Preorder;
use App\Models\Purchase;
use App\Models\Sparepart;
use App\Models\UsageRecord;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $summary = [
            'purchase_today' => Purchase::whereDate('purchase_date', $today)->sum('total_price'),
            'usage_today' => UsageRecord::whereDate('usage_date', $today)->sum('quantity'),
            'low_stock_count' => \App\Models\GardenStock::join('spareparts', 'spareparts.id', '=', 'garden_stocks.sparepart_id')
                ->whereColumn('garden_stocks.quantity', '<=', 'spareparts.min_stock')
                ->count(),
            'transactions_today' => Purchase::whereDate('purchase_date', $today)->count()
                + UsageRecord::whereDate('usage_date', $today)->count()
                + Preorder::whereDate('created_at', $today)->count(),
            'preorder_pending' => Preorder::whereIn('status', PreorderController::waitingStatuses(auth()->user()))->count(),
        ];

        return view('dashboard', compact('summary'));
    }
}
