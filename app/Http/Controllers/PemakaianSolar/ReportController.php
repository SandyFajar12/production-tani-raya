<?php

namespace App\Http\Controllers\PemakaianSolar;

use App\Http\Controllers\Controller;
use App\Models\Fleet;
use App\Models\PemakaianSolar\Trip;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ReportController extends Controller
{
    /** Susun baris laporan pemakaian solar per perjalanan, urut per armada lalu tanggal. */
    protected function buildRows(string $start, string $end, ?int $fleetId, string $search = '')
    {
        $trips = Trip::with(['fleet', 'garden'])
            ->whereDate('trip_date', '>=', $start)
            ->whereDate('trip_date', '<=', $end)
            ->when($fleetId, fn ($q) => $q->where('fleet_id', $fleetId))
            ->orderBy('trip_date')
            ->orderBy('id')
            ->get();

        $rows = $trips->map(function ($t) {
            $date = Carbon::parse($t->trip_date);

            return [
                'id' => $t->id,
                'fleet_id' => $t->fleet_id,
                'fleet' => $t->fleet->name ?? '-',
                'fleet_code' => $t->fleet->code ?? '-',
                'date_raw' => $date->toDateString(),
                'date' => $date->format('d/m/Y'),
                'opening' => (float) $t->opening_qty,
                'purchased' => (float) $t->purchased_qty,
                'stocked' => (float) $t->stocked_qty,
                'garden' => $t->garden->name ?? '',
                'remaining' => (float) $t->remaining_qty,
                'used' => (float) $t->used_qty,
                'notes' => trim((string) $t->notes),
            ];
        });

        if ($search !== '') {
            $rows = $rows->filter(fn ($r) => stripos($r['fleet'].' '.$r['fleet_code'].' '.$r['garden'].' '.$r['notes'], $search) !== false);
        }

        return $rows->sortBy([
            ['fleet', 'asc'],
            ['date_raw', 'asc'],
            ['id', 'asc'],
        ])->values();
    }

    /** Ringkasan per armada + total, dari semua baris yang cocok dengan filter. */
    protected function summarize($rows): array
    {
        $perFleet = $rows->groupBy('fleet_id')->map(function ($g) {
            $trips = $g->count();
            $used = round($g->sum('used'), 2);

            return [
                'fleet' => $g->first()['fleet'],
                'fleet_code' => $g->first()['fleet_code'],
                'trips' => $trips,
                'purchased' => round($g->sum('purchased'), 2),
                'stocked' => round($g->sum('stocked'), 2),
                'used' => $used,
                'avg' => $trips > 0 ? round($used / $trips, 2) : 0,
            ];
        })->sortBy('fleet')->values();

        $trips = $perFleet->sum('trips');
        $used = round($perFleet->sum('used'), 2);

        $totals = [
            'trips' => $trips,
            'purchased' => round($perFleet->sum('purchased'), 2),
            'stocked' => round($perFleet->sum('stocked'), 2),
            'used' => $used,
            'avg' => $trips > 0 ? round($used / $trips, 2) : 0,
        ];

        return [$perFleet, $totals];
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
        [$summary, $totals] = $this->summarize($all);

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

        return view('pemakaian-solar.reports.index', compact('rows', 'summary', 'totals', 'fleets', 'start', 'end', 'fleetId', 'search'));
    }

    public function export(Request $request)
    {
        [$start, $end, $fleetId, $search] = $this->filters($request);

        // Export memakai semua baris yang cocok dengan filter dan kata pencarian, bukan hanya satu halaman
        $rows = $this->buildRows($start, $end, $fleetId, $search);
        [$summary, $totals] = $this->summarize($rows);

        $fleetName = $fleetId ? (Fleet::find($fleetId)->name ?? '-') : 'Semua Armada';
        $filename = 'laporan-pemakaian-solar-'.$start.'-sd-'.$end.'.xlsx';

        require_once base_path('app/Libraries/SimpleXLSXGen.php');
        $class = class_exists('\Shuchkin\SimpleXLSXGen') ? '\Shuchkin\SimpleXLSXGen' : '\SimpleXLSXGen';

        $cell = fn ($v) => '<style border="thin">'.$v.'</style>';
        $head = fn ($v) => '<style bgcolor="#E7E9F3" border="thin"><b>'.$v.'</b></style>';
        $bold = fn ($v) => '<style bgcolor="#F7F8FC" border="thin"><b>'.$v.'</b></style>';
        // Angka liter dikirim sebagai angka (titik desimal) supaya bisa dijumlahkan di Excel
        $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');

        $title = 'Laporan Pemakaian Solar '.Carbon::parse($start)->format('d/m/Y').' - '.Carbon::parse($end)->format('d/m/Y');

        $data = [];
        $data[] = ['<b>'.$title.'</b>'];
        $data[] = ['Armada: '.$fleetName.($search !== '' ? ' | Pencarian: '.$search : '')];
        $data[] = [''];
        $data[] = array_map($head, ['Armada', 'Kode', 'Tanggal', 'Solar Awal (L)', 'Dibeli (L)', 'Di-stock (L)', 'Kebun Stok', 'Sisa Standby (L)', 'Pemakaian (L)', 'Keterangan Perjalanan']);

        foreach ($rows as $r) {
            $data[] = [
                $cell($r['fleet']),
                $cell($r['fleet_code']),
                $cell("\0".$r['date']), // "\0" = tetap tampil sebagai teks dd/mm/yyyy
                $cell($num($r['opening'])),
                $cell($num($r['purchased'])),
                $cell($num($r['stocked'])),
                $cell($r['garden'] !== '' ? $r['garden'] : '-'),
                $cell($num($r['remaining'])),
                $cell($num($r['used'])),
                $cell($r['notes'] !== '' ? str_replace(['<', '>'], ['(', ')'], $r['notes']) : '-'),
            ];
        }

        $data[] = [''];
        $data[] = ['<b>Ringkasan per Armada</b>'];
        $data[] = array_map($head, ['Armada', 'Kode', 'Jumlah Perjalanan', 'Total Dibeli (L)', 'Total Di-stock (L)', 'Total Pemakaian (L)', 'Rata-rata Pemakaian per Perjalanan (L)']);

        foreach ($summary as $s) {
            $data[] = [
                $cell($s['fleet']),
                $cell($s['fleet_code']),
                $cell((string) $s['trips']),
                $cell($num($s['purchased'])),
                $cell($num($s['stocked'])),
                $cell($num($s['used'])),
                $cell($num($s['avg'])),
            ];
        }

        $data[] = [
            $bold('TOTAL'),
            $bold(''),
            $bold((string) $totals['trips']),
            $bold($num($totals['purchased'])),
            $bold($num($totals['stocked'])),
            $bold($num($totals['used'])),
            $bold($num($totals['avg'])),
        ];

        // Matikan pesan Deprecated/Notice supaya tidak ikut tercetak ke dalam file Excel
        $oldLevel = error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $xlsx = $class::fromArray($data, 'Laporan');
        if (method_exists($xlsx, 'mergeCells')) {
            $xlsx->mergeCells('A1:J1');
        }
        $xlsx->downloadAs($filename);

        error_reporting($oldLevel);
        exit;
    }
}