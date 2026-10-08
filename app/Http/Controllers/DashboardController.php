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
            'low_stock_count' => Sparepart::whereColumn('current_stock', '<=', 'min_stock')->count(),
            'transactions_today' => Purchase::whereDate('purchase_date', $today)->count()
                + UsageRecord::whereDate('usage_date', $today)->count()
                + Preorder::whereDate('created_at', $today)->count(),
            'preorder_pending' => Preorder::where('status', 'pending')->count(),
        ];

        return view('dashboard', compact('summary'));
    }
}
