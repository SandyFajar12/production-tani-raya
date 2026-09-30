<?php

namespace App\Http\Controllers;

use App\Models\Preorder;
use App\Models\Purchase;
use App\Models\UsageRecord;

class TransactionController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $purchases = Purchase::with(['sparepart', 'supplier'])
            ->whereDate('purchase_date', $today)
            ->get()
            ->map(function ($p) {
                return [
                    'type' => 'purchase',
                    'title' => $p->sparepart->name.' × '.$p->quantity,
                    'meta' => $p->supplier->name ?? '-',
                    'value' => 'Rp'.number_format($p->total_price, 0, ',', '.'),
                    'time' => $p->created_at,
                ];
            });

        $usages = UsageRecord::with(['sparepart', 'fleet'])
            ->whereDate('usage_date', $today)
            ->get()
            ->map(function ($u) {
                return [
                    'type' => 'usage',
                    'title' => $u->sparepart->name,
                    'meta' => $u->fleet->name ?? '-',
                    'value' => '−'.$u->quantity,
                    'time' => $u->created_at,
                ];
            });

        $preorders = Preorder::with('sparepart')
            ->whereDate('created_at', $today)
            ->get()
            ->map(function ($po) {
                return [
                    'type' => 'preorder',
                    'title' => $po->sparepart->name.' — '.$po->quantity,
                    'meta' => ucfirst($po->status),
                    'value' => $po->code,
                    'time' => $po->created_at,
                ];
            });

        $transactions = $purchases->concat($usages)->concat($preorders)
            ->sortByDesc('time')
            ->values();

        return view('transactions.index', compact('transactions'));
    }
}