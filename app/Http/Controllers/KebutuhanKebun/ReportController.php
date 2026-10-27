<?php

namespace App\Http\Controllers\KebutuhanKebun;

use App\Http\Controllers\Controller;
use App\Models\Garden;
use App\Models\KebutuhanKebun\Movement;
use App\Models\KebutuhanKebun\Usage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    /**
     * Susun baris laporan pemakaian per kebun. Lama sejak pemakaian sebelumnya dihitung dari
     * seluruh riwayat (termasuk sebelum tanggal mulai) SEBELUM data dicari/dipaginasi,
     * jadi angkanya tidak berubah saat search atau pindah halaman.
     */
    protected function buildRows(string $start, string $end, ?int $gardenId, string $search = '')
    {
        $usages = Usage::with(['garden', 'item.unit'])
            ->whereDate('usage_date', '<=', $end)
            ->when($gardenId, fn ($q) => $q->where('garden_id', $gardenId))
            ->orderBy('usage_date')
            ->orderBy('id')
            ->get();

        // Saldo stok di kebun tepat setelah tiap pemakaian
        $balances = Movement::where('type', 'usage')
            ->where('reference_type', Usage::class)
            ->whereIn('reference_id', $usages->pluck('id'))
            ->pluck('balance_after', 'reference_id');

        $last = [];
        $rows = collect();

        foreach ($usages as $u) {
            $key = $u->garden_id.'-'.$u->item_id;
            $date = Carbon::parse($u->usage_date)->startOfDay();

            $days = isset($last[$key]) ? $last[$key]->diffInDays($date) : null;
            $last[$key] = $date;

            if ($date->toDateString() < $start) {
                continue;
            }

            $rows->push([
                'garden' => $u->garden->name ?? '-',
                'item_code' => $u->item->code ?? '-',
                'item' => $u->item->name ?? '-',
                'date_raw' => $date->toDateString(),
                'date' => $date->format('d/m/Y'),
                'quantity' => $u->quantity,
                'unit' => $u->item->unit->name ?? '',
                'balance' => $balances[$u->id] ?? null,
                'days' => $days,
                'notes' => trim((string) $u->notes),
                'notes_short' => Str::limit(trim((string) $u->notes), 40, '...'),
            ]);
        }

        if ($search !== '') {
            $rows = $rows->filter(fn ($r) => stripos($r['garden'].' '.$r['item_code'].' '.$r['item'].' '.$r['notes'], $search) !== false);
        }

        return $rows->sortBy([
            ['garden', 'asc'],
            ['item', 'asc'],
            ['date_raw', 'asc'],
        ])->values();
    }

    protected function filters(Request $request): array
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'garden_id' => ['nullable', 'exists:gardens,id'],
            'search' => ['nullable', 'string', 'max:100'],
        ], [
            'end_date.after_or_equal' => 'Tanggal akhir tidak boleh lebih awal dari tanggal mulai.',
        ]);

        return [
            $request->input('start_date', now()->startOfMonth()->toDateString()),
            $request->input('end_date', now()->toDateString()),
            $request->filled('garden_id') ? (int) $request->input('garden_id') : null,
            trim((string) $request->input('search', '')),
        ];
    }

    public function index(Request $request)
    {
        [$start, $end, $gardenId, $search] = $this->filters($request);

        $all = $this->buildRows($start, $end, $gardenId, $search);

        $perPage = $this->perPage();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $rows = new LengthAwarePaginator(
            $all->forPage($page, $perPage)->values(),
            $all->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $gardens = Garden::orderBy('name')->get();

        return view('kebutuhan-kebun.reports.index', compact('rows', 'gardens', 'start', 'end', 'gardenId', 'search'));
    }

    public function export(Request $request)
    {
        [$start, $end, $gardenId, $search] = $this->filters($request);

        // Export memakai semua baris yang cocok dengan filter dan kata pencarian, bukan hanya satu halaman
        $rows = $this->buildRows($start, $end, $gardenId, $search);
        $gardenName = $gardenId ? (Garden::find($gardenId)->name ?? '-') : 'Semua Kebun';
        $filename = 'laporan-pemakaian-kebutuhan-'.$start.'-sd-'.$end.'.xlsx';

        require_once base_path('app/Libraries/SimpleXLSXGen.php');
        $class = class_exists('\Shuchkin\SimpleXLSXGen') ? '\Shuchkin\SimpleXLSXGen' : '\SimpleXLSXGen';

        $cell = fn ($v) => '<style border="thin">'.$v.'</style>';
        $head = fn ($v) => '<style bgcolor="#E7E9F3" border="thin"><b>'.$v.'</b></style>';

        $title = 'Laporan Pemakaian Kebutuhan Kebun '.Carbon::parse($start)->format('d/m/Y').' - '.Carbon::parse($end)->format('d/m/Y');

        $data = [];
        $data[] = ['<b>'.$title.'</b>'];
        $data[] = ['Kebun: '.$gardenName.($search !== '' ? ' | Pencarian: '.$search : '')];
        $data[] = [''];
        $data[] = array_map($head, ['Kebun', 'Kode', 'Item', 'Tanggal Pemakaian', 'Qty', 'Stok Terakhir', 'Lama Sejak Pemakaian Sebelumnya', 'Catatan']);

        foreach ($rows as $r) {
            $data[] = [
                $cell($r['garden']),
                $cell($r['item_code']),
                $cell($r['item']),
                $cell("\0".$r['date']), // "\0" = tetap tampil sebagai teks dd/mm/yyyy
                $cell($r['quantity'].($r['unit'] ? ' '.$r['unit'] : '')),
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
            $xlsx->mergeCells('A1:H1');
        }
        $xlsx->downloadAs($filename);

        error_reporting($oldLevel);
        exit;
    }
}