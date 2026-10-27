<?php

namespace App\Http\Controllers\PemakaianSolar;

use App\Http\Controllers\Controller;
use App\Models\Garden;
use App\Models\PemakaianSolar\Adjustment;
use App\Models\PemakaianSolar\Movement;
use App\Models\PemakaianSolar\Stock;
use App\Services\LocationLookupService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StockController extends Controller
{
    public function index()
    {
        $stocks = Stock::all()->keyBy('garden_id');

        // Semua kebun aktif (stok 0 tetap tampil) + kebun nonaktif yang masih punya stok
        $gardens = Garden::orderBy('name')->get()
            ->filter(fn ($g) => $g->is_active || (float) optional($stocks->get($g->id))->quantity > 0)
            ->values();

        // Dari kartu "Stok Menipis" di dashboard: hanya kebun yang stoknya sudah sampai batas minimum
        if (request('low_stock')) {
            $gardens = $gardens->filter(function ($g) use ($stocks) {
                $s = $stocks->get($g->id);

                return $s && (float) $s->min_stock > 0 && (float) $s->quantity <= (float) $s->min_stock;
            })->values();
        }

        $formGardens = Garden::where('is_active', true)->orderBy('name')->get();
        $stockData = $stocks->map(fn ($s) => (float) $s->quantity)->all();

        return view('pemakaian-solar.stocks.index', compact('gardens', 'stocks', 'formGardens', 'stockData'));
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'garden_id' => ['required', Rule::exists('gardens', 'id')->where('is_active', true)],
            'to_garden_id' => ['nullable', 'required_if:type,transfer', Rule::exists('gardens', 'id')->where('is_active', true), 'different:garden_id'],
            'type' => ['required', 'in:opname,damaged_lost,other,transfer'],
            'physical_qty' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string'],
            'photo_data' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ], [
            'garden_id.required' => 'Kebun wajib dipilih.',
            'garden_id.exists' => 'Kebun yang dipilih tidak valid atau sudah nonaktif.',
            'to_garden_id.required_if' => 'Kebun tujuan wajib dipilih untuk transfer stok.',
            'to_garden_id.exists' => 'Kebun tujuan tidak valid atau sudah nonaktif.',
            'to_garden_id.different' => 'Kebun tujuan tidak boleh sama dengan kebun asal.',
            'physical_qty.numeric' => 'Jumlah liter harus berupa angka.',
        ]);

        $isTransfer = $data['type'] === 'transfer';
        if (! $isTransfer) {
            $data['to_garden_id'] = null;
        }

        $locationLabel = null;
        if (! empty($data['latitude']) && ! empty($data['longitude'])) {
            $locationLabel = app(LocationLookupService::class)->nearestLabel((float) $data['latitude'], (float) $data['longitude']);
        }

        DB::transaction(function () use ($data, $isTransfer, $locationLabel) {
            $gardenId = (int) $data['garden_id'];
            $qty = round((float) $data['physical_qty'], 2);

            $before = round((float) Stock::where('garden_id', $gardenId)->lockForUpdate()->value('quantity'), 2);

            if ($isTransfer) {
                if ($qty <= 0) {
                    throw ValidationException::withMessages(['physical_qty' => 'Jumlah dikirim harus lebih dari 0 liter.']);
                }
                if ($qty > $before) {
                    throw ValidationException::withMessages(['physical_qty' => 'Jumlah dikirim ('.Stock::fmt($qty).' L) melebihi stok di kebun asal ('.Stock::fmt($before).' L).']);
                }
                $difference = -$qty;
            } else {
                $difference = round($qty - $before, 2);
            }

            $photoPath = ! empty($data['photo_data']) ? $this->savePhoto($data['photo_data']) : null;

            $adjustment = Adjustment::create([
                'garden_id' => $gardenId,
                'to_garden_id' => $data['to_garden_id'],
                'type' => $data['type'],
                'system_qty_before' => $before,
                'physical_qty' => $qty,
                'difference' => $difference,
                'reason' => $data['reason'],
                'adjusted_by' => auth()->id(),
                'photo_path' => $photoPath,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'location_label' => $locationLabel,
            ]);

            if ($isTransfer) {
                Movement::record($gardenId, 'transfer_out', Adjustment::class, $adjustment->id, -$qty);
                Movement::record((int) $data['to_garden_id'], 'transfer_in', Adjustment::class, $adjustment->id, $qty);
            } else {
                Movement::record($gardenId, 'adjustment', Adjustment::class, $adjustment->id, $difference);
            }
        });

        return back()->with('status', $isTransfer ? 'Transfer stok solar tersimpan.' : 'Penyesuaian stok solar tersimpan.');
    }

    /** Atur stok minimum solar per kebun (0 = notifikasi stok menipis nonaktif) */
    public function setMinimum(Request $request)
    {
        $data = $request->validate([
            'garden_id' => ['required', Rule::exists('gardens', 'id')->where('is_active', true)],
            'min_stock' => ['required', 'numeric', 'min:0'],
        ], [
            'garden_id.required' => 'Kebun wajib dipilih.',
            'garden_id.exists' => 'Kebun yang dipilih tidak valid atau sudah nonaktif.',
            'min_stock.required' => 'Stok minimum wajib diisi (isi 0 untuk menonaktifkan).',
            'min_stock.numeric' => 'Stok minimum harus berupa angka (liter).',
        ]);

        $stock = Stock::firstOrCreate(['garden_id' => $data['garden_id']], ['quantity' => 0]);
        $stock->min_stock = round((float) $data['min_stock'], 2);
        $stock->save();

        return back()->with('status', 'Stok minimum solar tersimpan.');
    }

    private function savePhoto(string $dataUrl): string
    {
        if (! preg_match('#^data:image/(jpeg|png);base64,#', $dataUrl)) {
            throw ValidationException::withMessages(['photo_data' => 'Format foto tidak valid, ambil ulang foto.']);
        }

        [, $content] = explode(',', $dataUrl, 2);
        $decoded = base64_decode($content, true);

        if ($decoded === false) {
            throw ValidationException::withMessages(['photo_data' => 'Foto rusak, ambil ulang foto.']);
        }

        $filename = 'solar-stok-'.now()->format('Ymd-His').'-'.uniqid().'.jpg';
        $directory = storage_path('app/public/stock-photos');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($directory.'/'.$filename, $decoded);

        return $filename;
    }

    public function photo(string $filename)
    {
        // Cegah path traversal: hanya nama file polos
        if (str_contains($filename, '/') || str_contains($filename, '..')) {
            abort(404);
        }

        $path = storage_path('app/public/stock-photos/'.$filename);

        if (! file_exists($path)) {
            abort(404);
        }

        return response()->file($path);
    }

    public function ledger(Request $request)
    {
        $gardens = Garden::orderBy('name')->get();
        $gardenId = $request->input('garden_id');
        $search = trim((string) $request->input('search', ''));

        // Kata pencarian yang dikenali sebagai jenis pergerakan
        $typeLabels = [
            'Perjalanan' => ['trip_stock'],
            'Isi Tangki' => ['fill_vehicle'],
            'Penyesuaian' => ['adjustment'],
            'Opname' => ['adjustment'],
            'Transfer Masuk' => ['transfer_in'],
            'Transfer Keluar' => ['transfer_out'],
            'Transfer' => ['transfer_in', 'transfer_out'],
            'Masuk' => ['trip_stock', 'transfer_in'],
            'Keluar' => ['fill_vehicle', 'transfer_out'],
        ];

        $movements = Movement::with('garden')
            ->when($gardenId, fn ($q) => $q->where('garden_id', $gardenId))
            ->when($search !== '', function ($q) use ($search, $typeLabels) {
                $types = collect($typeLabels)
                    ->filter(fn ($v, $label) => stripos($label, $search) !== false)
                    ->flatten()->unique()->all();

                $q->where(function ($w) use ($search, $types) {
                    $w->whereHas('garden', fn ($g) => $g->where('name', 'like', "%{$search}%"));
                    if ($types) {
                        $w->orWhereIn('type', $types);
                    }
                });
            })
            ->orderByDesc('id')
            ->paginate($this->perPage())
            ->withQueryString();

        $details = $this->ledgerDetails($movements->getCollection());

        return view('pemakaian-solar.stocks.ledger', compact('gardens', 'gardenId', 'movements', 'details'));
    }

    /**
     * Detail tiap baris Kartu Stok (judul + baris isi modal), diambil sekaligus supaya tidak query satu-satu di view.
     * Detail perjalanan dan isi tangki ditambahkan di langkah berikutnya.
     */
    private function ledgerDetails(Collection $movements): array
    {
        $adjustmentIds = $movements->whereIn('type', ['adjustment', 'transfer_in', 'transfer_out'])->pluck('reference_id');
        $adjustments = Adjustment::with(['adjuster', 'garden', 'toGarden'])->whereIn('id', $adjustmentIds)->get()->keyBy('id');

        $adjLabel = [
            'opname' => 'Stok Opname', 'damaged_lost' => 'Barang Rusak / Hilang',
            'other' => 'Lainnya', 'transfer' => 'Transfer Stok',
        ];

        $details = [];

        foreach ($movements as $m) {
            if (in_array($m->type, ['adjustment', 'transfer_in', 'transfer_out']) && $adjustments->has($m->reference_id)) {
                $adj = $adjustments[$m->reference_id];
                $photo = $adj->photo_path ? route('solar.stocks.photo', $adj->photo_path) : null;

                if ($adj->type === 'transfer') {
                    $details[$m->id] = [
                        'title' => 'Detail Transfer Stok Solar',
                        'rows' => [
                            '__photo__' => $photo,
                            'Dari Kebun' => $adj->garden->name ?? '-',
                            'Ke Kebun' => $adj->toGarden->name ?? '-',
                            'Jumlah Dikirim' => Stock::fmt($adj->physical_qty).' L',
                            'Catatan' => $adj->reason ?? '-',
                            'Lokasi' => $adj->location_label ?? '-',
                            'Dilakukan Oleh' => $adj->adjuster->name ?? '-',
                        ],
                    ];
                } else {
                    $details[$m->id] = [
                        'title' => 'Detail Penyesuaian Stok Solar',
                        'rows' => [
                            '__photo__' => $photo,
                            'Jenis' => $adjLabel[$adj->type] ?? ucfirst($adj->type),
                            'Kebun' => $adj->garden->name ?? '-',
                            'Stok Sebelum' => Stock::fmt($adj->system_qty_before).' L',
                            'Stok Fisik' => Stock::fmt($adj->physical_qty).' L',
                            'Selisih' => Stock::fmt($adj->difference).' L',
                            'Catatan' => $adj->reason ?? '-',
                            'Lokasi' => $adj->location_label ?? '-',
                            'Koordinat' => ($adj->latitude && $adj->longitude) ? $adj->latitude.', '.$adj->longitude : '-',
                            'Dilakukan Oleh' => $adj->adjuster->name ?? '-',
                        ],
                    ];
                }
            }
        }

        // Detail perjalanan (solar masuk ke stok kebun)
        $trips = \App\Models\PemakaianSolar\Trip::with(['fleet', 'garden', 'recorder'])
            ->whereIn('id', $movements->where('type', 'trip_stock')->pluck('reference_id'))
            ->get()->keyBy('id');

        foreach ($movements as $m) {
            if ($m->type === 'trip_stock' && $trips->has($m->reference_id)) {
                $trip = $trips[$m->reference_id];

                $details[$m->id] = [
                    'title' => 'Detail Stok dari Perjalanan',
                    'rows' => [
                        'Armada' => ($trip->fleet->name ?? '-').' — '.($trip->fleet->code ?? '-'),
                        'Tanggal' => \Carbon\Carbon::parse($trip->trip_date)->format('d M Y'),
                        'Solar Dibeli' => Stock::fmt($trip->purchased_qty).' L',
                        'Solar di-Stock' => Stock::fmt($trip->stocked_qty).' L',
                        'Kebun Tempat Stok' => $trip->garden->name ?? '-',
                        'Pemakaian Perjalanan' => Stock::fmt($trip->used_qty).' L',
                        'Sisa Standby Armada' => Stock::fmt($trip->remaining_qty).' L',
                        'Keterangan Perjalanan' => $trip->notes ?: '-',
                        'Dicatat Oleh' => $trip->recorder->name ?? '-',
                    ],
                ];
            }
        }

        // Detail isi tangki armada (solar keluar dari stok kebun)
        $fills = \App\Models\PemakaianSolar\Fill::with(['fleet', 'garden', 'recorder'])
            ->whereIn('id', $movements->where('type', 'fill_vehicle')->pluck('reference_id'))
            ->get()->keyBy('id');

        foreach ($movements as $m) {
            if ($m->type === 'fill_vehicle' && $fills->has($m->reference_id)) {
                $fill = $fills[$m->reference_id];

                $details[$m->id] = [
                    'title' => 'Detail Isi Tangki Armada',
                    'rows' => [
                        'Armada' => ($fill->fleet->name ?? '-').' — '.($fill->fleet->code ?? '-'),
                        'Tanggal' => \Carbon\Carbon::parse($fill->fill_date)->format('d M Y'),
                        'Kebun Sumber' => $fill->garden->name ?? '-',
                        'Jumlah Diisi' => Stock::fmt($fill->quantity).' L',
                        'Saldo Armada Sebelum' => Stock::fmt($fill->balance_before).' L',
                        'Saldo Armada Sesudah' => Stock::fmt($fill->balance_after).' L',
                        'Catatan' => $fill->notes ?: '-',
                        'Dicatat Oleh' => $fill->recorder->name ?? '-',
                    ],
                ];
            }
        }

        return $details;
    }
}