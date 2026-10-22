<?php

namespace App\Http\Controllers\PemakaianSolar;

use App\Http\Controllers\Controller;
use App\Models\PemakaianSolar\Adjustment;
use App\Models\PemakaianSolar\BalanceAdjustment;
use App\Models\PemakaianSolar\Fill;
use App\Models\PemakaianSolar\Stock;
use App\Models\PemakaianSolar\Trip;

class TransactionController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        $fmt = fn ($v) => Stock::fmt($v);
        $signed = fn ($v) => ((float) $v > 0 ? '+' : '').Stock::fmt($v).' L';

        $trips = Trip::with(['fleet', 'garden'])
            ->whereDate('trip_date', $today)
            ->get()
            ->map(function ($t) use ($fmt) {
                return [
                    'type' => 'trip',
                    'title' => $t->fleet->name ?? '-',
                    'meta' => 'Dibeli '.$fmt($t->purchased_qty).' L · Di-stock '.$fmt($t->stocked_qty).' L'.($t->garden ? ' ('.$t->garden->name.')' : ''),
                    'value' => 'Pakai '.$fmt($t->used_qty).' L',
                    'time' => $t->created_at,
                ];
            });

        $fills = Fill::with(['fleet', 'garden'])
            ->whereDate('fill_date', $today)
            ->get()
            ->map(function ($f) use ($fmt) {
                return [
                    'type' => 'fill',
                    'title' => $f->fleet->name ?? '-',
                    'meta' => 'Dari '.($f->garden->name ?? '-'),
                    'value' => '+'.$fmt($f->quantity).' L',
                    'time' => $f->created_at,
                ];
            });

        $adjLabel = [
            'opname' => 'Stok Opname', 'damaged_lost' => 'Rusak / Hilang / Tumpah',
            'other' => 'Lainnya', 'transfer' => 'Transfer Stok',
        ];

        $adjustments = Adjustment::with(['garden', 'toGarden'])
            ->whereDate('created_at', $today)
            ->get()
            ->map(function ($a) use ($adjLabel, $signed) {
                $meta = $adjLabel[$a->type] ?? ucfirst($a->type);
                if ($a->type === 'transfer') {
                    $meta .= ' ke '.($a->toGarden->name ?? '-');
                }

                return [
                    'type' => 'adjustment',
                    'title' => $a->garden->name ?? '-',
                    'meta' => $meta,
                    'value' => $signed($a->difference),
                    'time' => $a->created_at,
                ];
            });

        $corrections = BalanceAdjustment::with('fleet')
            ->whereDate('created_at', $today)
            ->get()
            ->map(function ($b) use ($signed) {
                return [
                    'type' => 'balance',
                    'title' => $b->fleet->name ?? '-',
                    'meta' => 'Koreksi saldo armada',
                    'value' => $signed($b->difference),
                    'time' => $b->created_at,
                ];
            });

        $transactions = $trips->concat($fills)->concat($adjustments)->concat($corrections)
            ->sortByDesc('time')
            ->values();

        return view('pemakaian-solar.transactions.index', compact('transactions'));
    }
}