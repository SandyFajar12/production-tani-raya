<?php

namespace App\Http\Controllers;

use App\Models\Preorder;
use App\Models\Purchase;
use App\Models\StockAdjustment;
use App\Models\UsageRecord;
use Illuminate\Http\Request;

class TransactionReportController extends Controller
{
    protected function buildTransactions(string $start, string $end)
    {
        $purchases = Purchase::with(['sparepart', 'supplier', 'recorder'])
            ->whereBetween('purchase_date', [$start, $end])
            ->get()
            ->map(fn ($p) => [
                'date' => $p->purchase_date,
                'type' => 'Pembelian',
                'sparepart' => $p->sparepart->name ?? '-',
                'quantity' => $p->quantity,
                'value' => $p->total_price,
                'related' => $p->supplier->name ?? '-',
                'recorded_by' => $p->recorder->name ?? '-',
            ]);

        $usages = UsageRecord::with(['sparepart', 'fleet', 'recorder'])
            ->whereBetween('usage_date', [$start, $end])
            ->get()
            ->map(fn ($u) => [
                'date' => $u->usage_date,
                'type' => 'Pemakaian',
                'sparepart' => $u->sparepart->name ?? '-',
                'quantity' => $u->quantity,
                'value' => null,
                'related' => $u->fleet->name ?? '-',
                'recorded_by' => $u->recorder->name ?? '-',
            ]);

        $preorders = Preorder::with(['sparepart', 'requester'])
            ->whereBetween('created_at', ["$start 00:00:00", "$end 23:59:59"])
            ->get()
            ->map(fn ($po) => [
                'date' => $po->created_at->toDateString(),
                'type' => 'Pre-Order ('.ucfirst($po->status).')',
                'sparepart' => $po->sparepart->name ?? '-',
                'quantity' => $po->quantity,
                'value' => null,
                'related' => $po->code,
                'recorded_by' => $po->requester->name ?? '-',
            ]);

        $adjustments = StockAdjustment::with(['sparepart', 'adjuster'])
            ->whereBetween('created_at', ["$start 00:00:00", "$end 23:59:59"])
            ->get()
            ->map(fn ($a) => [
                'date' => $a->created_at->toDateString(),
                'type' => 'Update Stok',
                'sparepart' => $a->sparepart->name ?? '-',
                'quantity' => $a->difference,
                'value' => null,
                'related' => $a->reason,
                'recorded_by' => $a->adjuster->name ?? '-',
            ]);

        return $purchases->concat($usages)->concat($preorders)->concat($adjustments)
            ->sortBy('date')
            ->values();
    }

    public function index(Request $request)
    {
        $start = $request->input('start_date', now()->startOfMonth()->toDateString());
        $end = $request->input('end_date', now()->toDateString());

        $transactions = $this->buildTransactions($start, $end);

        return view('report.index', compact('transactions', 'start', 'end'));
    }

    public function export(Request $request)
    {
        $start = $request->input('start_date', now()->startOfMonth()->toDateString());
        $end = $request->input('end_date', now()->toDateString());

        $transactions = $this->buildTransactions($start, $end);
        $filename = 'laporan-transaksi-'.$start.'-sd-'.$end.'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Tanggal', 'Jenis Transaksi', 'Sparepart', 'Jumlah', 'Nilai (Rp)', 'Terkait', 'Dicatat Oleh']);

            foreach ($transactions as $t) {
                fputcsv($handle, [
                    $t['date'], $t['type'], $t['sparepart'], $t['quantity'],
                    $t['value'], $t['related'], $t['recorded_by'],
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}