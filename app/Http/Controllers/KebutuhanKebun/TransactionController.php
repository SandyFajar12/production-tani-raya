<?php

namespace App\Http\Controllers\KebutuhanKebun;

use App\Http\Controllers\Controller;
use App\Models\KebutuhanKebun\Preorder;
use App\Models\KebutuhanKebun\Purchase;
use App\Models\KebutuhanKebun\Usage;

class TransactionController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $purchases = Purchase::with(['item', 'supplier'])
            ->whereDate('purchase_date', $today)
            ->get()
            ->map(function ($p) {
                return [
                    'type' => 'purchase',
                    'title' => ($p->item->name ?? '-').' × '.$p->quantity,
                    'meta' => $p->supplier->name ?? '-',
                    'value' => 'Rp'.number_format($p->total_price, 0, ',', '.'),
                    'time' => $p->created_at,
                ];
            });

        $usages = Usage::with(['item', 'garden'])
            ->whereDate('usage_date', $today)
            ->get()
            ->map(function ($u) {
                return [
                    'type' => 'usage',
                    'title' => $u->item->name ?? '-',
                    'meta' => $u->garden->name ?? '-',
                    'value' => '−'.$u->quantity,
                    'time' => $u->created_at,
                ];
            });

        $preorders = Preorder::with('item')
            ->whereDate('created_at', $today)
            ->get()
            ->map(function ($po) {
                return [
                    'type' => 'preorder',
                    'title' => ($po->item->name ?? '-').' — '.$po->quantity,
                    'meta' => ucfirst($po->status),
                    'value' => $po->code,
                    'time' => $po->created_at,
                ];
            });

        $transactions = $purchases->concat($usages)->concat($preorders)
            ->sortByDesc('time')
            ->values();

        return view('kebutuhan-kebun.transactions.index', compact('transactions'));
    }
}