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
        $filename = 'laporan-transaksi-'.$start.'-sd-'.$end.'.xlsx';

        require_once base_path('app/Libraries/SimpleXLSXGen.php');
        $class = class_exists('\Shuchkin\SimpleXLSXGen') ? '\Shuchkin\SimpleXLSXGen' : '\SimpleXLSXGen';

        $rupiah = fn ($n) => 'Rp'.number_format((float) $n, 0, ',', '.');
        $date = fn ($d) => "\0".\Carbon\Carbon::parse($d)->format('d/m/Y'); // "\0" = tetap tampil sebagai teks dd/mm/yyyy
        $cell = fn ($v) => '<style border="thin">'.$v.'</style>';
        $head = fn ($v) => '<style bgcolor="#E7E9F3" border="thin"><b>'.$v.'</b></style>';

        $title = 'Laporan Transaksi '.\Carbon\Carbon::parse($start)->format('d/m/Y').' - '.\Carbon\Carbon::parse($end)->format('d/m/Y');

        $rows = [];
        $rows[] = ['<b>'.$title.'</b>'];
        $rows[] = [''];
        $rows[] = array_map($head, ['Tanggal', 'Jenis Transaksi', 'Sparepart', 'Jumlah', 'Nilai (Rp)', 'Terkait', 'Dicatat Oleh']);

        $totalPurchase = 0;
        foreach ($transactions as $t) {
            if ($t['type'] === 'Pembelian') {
                $totalPurchase += (float) $t['value'];
            }

            $rows[] = [
                $cell($date($t['date'])),
                $cell($t['type']),
                $cell($t['sparepart']),
                $cell($t['quantity']),
                $cell($t['value'] ? $rupiah($t['value']) : ''),
                $cell($t['related']),
                $cell($t['recorded_by']),
            ];
        }

        $rows[] = [
            '', '', '',
            '<b>Total Pembelian</b>',
            '<b>'.$rupiah($totalPurchase).'</b>',
            '', '',
        ];

        // Matikan pesan Deprecated/Notice supaya tidak ikut tercetak ke dalam file Excel
        $oldLevel = error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $xlsx = $class::fromArray($rows, 'Laporan');
        if (method_exists($xlsx, 'mergeCells')) {
            $xlsx->mergeCells('A1:G1');
        }
        $xlsx->downloadAs($filename);

        error_reporting($oldLevel);
        exit;
    }
}