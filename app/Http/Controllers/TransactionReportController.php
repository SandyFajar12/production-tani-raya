<?php

namespace App\Http\Controllers;

use App\Models\Fleet;
use App\Models\StockMovement;
use App\Models\UsageRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class TransactionReportController extends Controller
{
    /**
     * Susun baris laporan pemakaian. Lama sejak penggantian sebelumnya dihitung dari
     * seluruh riwayat (termasuk sebelum tanggal mulai) SEBELUM data dicari/dipaginasi,
     * jadi angkanya tidak berubah saat search atau pindah halaman.
     */
    protected function buildRows(string $start, string $end, ?int $fleetId, string $search = '')
    {
        $usages = UsageRecord::with(['fleet', 'sparepart.unit', 'garden'])
            ->whereDate('usage_date', '<=', $end)
            ->when($fleetId, fn ($q) => $q->where('fleet_id', $fleetId))
            ->orderBy('usage_date')
            ->orderBy('id')
            ->get();

        // Saldo stok di kebun tepat setelah tiap pemakaian
        $balances = StockMovement::where('type', 'usage')
            ->where('reference_type', UsageRecord::class)
            ->whereIn('reference_id', $usages->pluck('id'))
            ->pluck('balance_after', 'reference_id');

        $last = [];
        $rows = collect();

        foreach ($usages as $u) {
            $key = $u->fleet_id.'-'.$u->sparepart_id;
            $date = Carbon::parse($u->usage_date)->startOfDay();

            $days = isset($last[$key]) ? $last[$key]->diffInDays($date) : null;
            $last[$key] = $date;

            if ($date->toDateString() < $start) {
                continue;
            }

            $rows->push([
                'fleet' => $u->fleet->name ?? '-',
                'fleet_code' => $u->fleet->code ?? '-',
                'sparepart' => $u->sparepart->name ?? '-',
                'date_raw' => $date->toDateString(),
                'date' => $date->format('d/m/Y'),
                'quantity' => $u->quantity,
                'unit' => $u->sparepart->unit->name ?? '',
                'garden' => $u->garden->name ?? '-',
                'balance' => $balances[$u->id] ?? null,
                'days' => $days,
                'notes' => trim((string) $u->notes),
                'notes_short' => Str::limit(trim((string) $u->notes), 40, '...'),
            ]);
        }

        if ($search !== '') {
            $rows = $rows->filter(fn ($r) => stripos($r['fleet'].' '.$r['fleet_code'].' '.$r['sparepart'].' '.$r['garden'].' '.$r['notes'], $search) !== false);
        }

        return $rows->sortBy([
            ['fleet', 'asc'],
            ['sparepart', 'asc'],
            ['date_raw', 'asc'],
        ])->values();
    }

    protected function filters(Request $request): array
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'fleet_id' => ['nullable', 'exists:fleets,id'],
            'search' => ['nullable', 'string', 'max:100'],
        ], [
            'end_date.after_or_equal' => 'Tanggal akhir tidak boleh lebih awal dari tanggal mulai.',
        ]);

        return [
            $request->input('start_date', now()->startOfMonth()->toDateString()),
            $request->input('end_date', now()->toDateString()),
            $request->filled('fleet_id') ? (int) $request->input('fleet_id') : null,
            trim((string) $request->input('search', '')),
        ];
    }

    public function index(Request $request)
    {
        [$start, $end, $fleetId, $search] = $this->filters($request);

        $all = $this->buildRows($start, $end, $fleetId, $search);

        $perPage = $this->perPage();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $rows = new LengthAwarePaginator(
            $all->forPage($page, $perPage)->values(),
            $all->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $fleets = Fleet::orderBy('name')->get();

        return view('stock-sparepart.report.index', compact('rows', 'fleets', 'start', 'end', 'fleetId', 'search'));
    }

    public function export(Request $request)
    {
        [$start, $end, $fleetId, $search] = $this->filters($request);

        // Export memakai semua baris yang cocok dengan filter dan kata pencarian, bukan hanya satu halaman
        $rows = $this->buildRows($start, $end, $fleetId, $search);
        $fleetName = $fleetId ? (Fleet::find($fleetId)->name ?? '-') : 'Semua Armada';
        $filename = 'laporan-pemakaian-'.$start.'-sd-'.$end.'.xlsx';

        require_once base_path('app/Libraries/SimpleXLSXGen.php');
        $class = class_exists('\Shuchkin\SimpleXLSXGen') ? '\Shuchkin\SimpleXLSXGen' : '\SimpleXLSXGen';

        $cell = fn ($v) => '<style border="thin">'.$v.'</style>';
        $head = fn ($v) => '<style bgcolor="#E7E9F3" border="thin"><b>'.$v.'</b></style>';

        $title = 'Laporan Pemakaian Sparepart '.Carbon::parse($start)->format('d/m/Y').' - '.Carbon::parse($end)->format('d/m/Y');

        $data = [];
        $data[] = ['<b>'.$title.'</b>'];
        $data[] = ['Armada: '.$fleetName.($search !== '' ? ' | Pencarian: '.$search : '')];
        $data[] = [''];
        $data[] = array_map($head, ['Armada', 'Kode', 'Sparepart', 'Tanggal Pemakaian', 'Qty', 'Lokasi Kebun', 'Stok Terakhir', 'Lama Sejak Penggantian Sebelumnya', 'Catatan']);

        foreach ($rows as $r) {
            $data[] = [
                $cell($r['fleet']),
                $cell($r['fleet_code']),
                $cell($r['sparepart']),
                $cell("\0".$r['date']), // "\0" = tetap tampil sebagai teks dd/mm/yyyy
                $cell($r['quantity'].($r['unit'] ? ' '.$r['unit'] : '')),
                $cell($r['garden']),
                $cell($r['balance'] !== null ? $r['balance'].($r['unit'] ? ' '.$r['unit'] : '') : '-'),
                $cell($r['days'] !== null ? $r['days'].' hari' : '-'),
                $cell($r['notes'] !== '' ? str_replace(['<', '>'], ['(', ')'], $r['notes']) : '-'),
            ];
        }

        // Matikan pesan Deprecated/Notice supaya tidak ikut tercetak ke dalam file Excel
        $oldLevel = error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $xlsx = $class::fromArray($data, 'Laporan');
        if (method_exists($xlsx, 'mergeCells')) {
            $xlsx->mergeCells('A1:I1');
        }
        $xlsx->downloadAs($filename);

        error_reporting($oldLevel);
        exit;
    }
}