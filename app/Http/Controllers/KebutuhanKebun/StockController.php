<?php

namespace App\Http\Controllers\KebutuhanKebun;

use App\Http\Controllers\Controller;
use App\Models\Garden;
use App\Models\KebutuhanKebun\Adjustment;
use App\Models\KebutuhanKebun\GardenStock;
use App\Models\KebutuhanKebun\Item;
use App\Models\KebutuhanKebun\Movement;
use App\Models\KebutuhanKebun\Preorder;
use App\Services\LocationLookupService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StockController extends Controller
{
    public function index()
    {
        // Satu baris per item per kebun
        $query = GardenStock::with(['item.unit', 'item.category', 'item.gardenStocks.garden', 'garden'])
            ->join('kebun_items', 'kebun_items.id', '=', 'kebun_garden_stocks.item_id')
            ->join('gardens', 'gardens.id', '=', 'kebun_garden_stocks.garden_id')
            ->select('kebun_garden_stocks.*');

        $search = trim((string) request('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('kebun_items.code', 'like', "%{$search}%")
                  ->orWhere('kebun_items.name', 'like', "%{$search}%")
                  ->orWhere('gardens.name', 'like', "%{$search}%");
            });
        }

        if (request('garden_id')) {
            $query->where('kebun_garden_stocks.garden_id', (int) request('garden_id'));
        }

        if (request('low_stock')) {
            $query->whereColumn('kebun_garden_stocks.quantity', '<=', 'kebun_items.min_stock');
        }

        $stocks = $query->orderBy('kebun_items.name')->orderBy('gardens.name')
            ->paginate($this->perPage())->withQueryString();

        // Dropdown form: semua item (bukan hanya yang tampil di halaman), beserta stok per kebun
        $formItems = Item::with('gardenStocks')->orderBy('name')->get();
        $gardens = Garden::where('is_active', true)->orderBy('name')->get();
        $allGardens = Garden::orderBy('name')->get();

        return view('kebutuhan-kebun.stocks.index', compact('stocks', 'formItems', 'gardens', 'allGardens'));
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:kebun_items,id'],
            'garden_id' => ['required', Rule::exists('gardens', 'id')->where('is_active', true)],
            'to_garden_id' => ['nullable', 'required_if:type,transfer', Rule::exists('gardens', 'id')->where('is_active', true), 'different:garden_id'],
            'type' => ['required', 'in:opname,damaged_lost,return_to_supplier,other,transfer'],
            'physical_qty' => ['required', 'integer', 'min:0'],
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
            $item = Item::lockForUpdate()->findOrFail($data['item_id']);
            $gardenId = (int) $data['garden_id'];
            $qty = (int) $data['physical_qty'];

            $before = (int) GardenStock::where('item_id', $item->id)
                ->where('garden_id', $gardenId)
                ->lockForUpdate()
                ->value('quantity');

            if ($isTransfer) {
                if ($qty < 1) {
                    throw ValidationException::withMessages(['physical_qty' => 'Jumlah dikirim minimal 1.']);
                }
                if ($qty > $before) {
                    throw ValidationException::withMessages(['physical_qty' => 'Jumlah dikirim ('.$qty.') melebihi stok di kebun asal ('.$before.').']);
                }
                $difference = -$qty;
            } else {
                $difference = $qty - $before;
            }

            $photoPath = ! empty($data['photo_data']) ? $this->savePhoto($data['photo_data']) : null;

            $adjustment = Adjustment::create([
                'item_id' => $item->id,
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
                Movement::record($item->id, 'transfer_out', Adjustment::class, $adjustment->id, -$qty, $gardenId);
                Movement::record($item->id, 'transfer_in', Adjustment::class, $adjustment->id, $qty, (int) $data['to_garden_id']);
                $this->fulfillPreorders($item->id, (int) $data['to_garden_id'], $qty);
            } else {
                Movement::record($item->id, 'adjustment', Adjustment::class, $adjustment->id, $difference, $gardenId);
            }
        });

        return back()->with('status', $isTransfer ? 'Transfer stok tersimpan.' : 'Penyesuaian stok tersimpan.');
    }

    /**
     * Jumlah yang ditransfer dibagi ke pre-order berstatus "Dibeli" dengan item dan kebun tujuan yang sama,
     * dimulai dari yang paling lama. Pre-order yang jumlahnya terpenuhi berubah jadi "Dikirim".
     */
    private function fulfillPreorders(int $itemId, int $toGardenId, int $qty): void
    {
        $open = Preorder::where('item_id', $itemId)
            ->where('garden_id', $toGardenId)
            ->where('status', 'purchased')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($open as $po) {
            if ($qty <= 0) {
                break;
            }

            $need = $po->quantity - $po->shipped_quantity;
            if ($need <= 0) {
                continue;
            }

            $give = min($need, $qty);
            $po->shipped_quantity += $give;

            if ($po->shipped_quantity >= $po->quantity) {
                $po->status = 'shipped';
            }

            $po->save();
            $qty -= $give;

            if ($po->status === 'shipped') {
                NotificationService::kebunPreorderShipped($po);
            }
        }
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

        $filename = 'kebun-stok-'.now()->format('Ymd-His').'-'.uniqid().'.jpg';
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
        $items = Item::orderBy('name')->get();
        $gardens = Garden::orderBy('name')->get();

        $selectedId = $request->input('item_id', $items->first()->id ?? null);
        $selected = $selectedId ? Item::find($selectedId) : null;
        $gardenId = $request->input('garden_id');
        $search = trim((string) $request->input('search', ''));

        // Kata pencarian yang dikenali sebagai jenis pergerakan
        $typeLabels = [
            'Pembelian' => ['purchase'],
            'Pemakaian' => ['usage'],
            'Penyesuaian' => ['adjustment'],
            'Opname' => ['adjustment'],
            'Transfer Masuk' => ['transfer_in'],
            'Transfer Keluar' => ['transfer_out'],
            'Transfer' => ['transfer_in', 'transfer_out'],
            'Masuk' => ['purchase', 'transfer_in'],
            'Keluar' => ['usage', 'transfer_out'],
        ];

        $movements = Movement::with('garden')
            ->where('item_id', $selected->id ?? 0)
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

        return view('kebutuhan-kebun.stocks.ledger', compact('items', 'gardens', 'selected', 'gardenId', 'movements', 'details'));
    }

    /**
     * Detail tiap baris Kartu Stok (judul + baris isi modal), diambil sekaligus supaya tidak query satu-satu di view.
     * Detail pembelian dan pemakaian ditambahkan di langkah berikutnya.
     */
    private function ledgerDetails(Collection $movements): array
    {
        $adjustmentIds = $movements->whereIn('type', ['adjustment', 'transfer_in', 'transfer_out'])->pluck('reference_id');
        $adjustments = Adjustment::with(['adjuster', 'garden', 'toGarden'])->whereIn('id', $adjustmentIds)->get()->keyBy('id');

        $purchaseIds = $movements->where('type', 'purchase')->pluck('reference_id');
        $purchases = \App\Models\KebutuhanKebun\Purchase::with(['supplier', 'recorder', 'receivedGarden', 'targetGarden'])
            ->whereIn('id', $purchaseIds)->get()->keyBy('id');

        $adjLabel = [
            'opname' => 'Stok Opname', 'damaged_lost' => 'Barang Rusak / Hilang',
            'return_to_supplier' => 'Retur ke Supplier', 'other' => 'Lainnya', 'transfer' => 'Transfer Stok',
        ];

        $details = [];

        foreach ($movements as $m) {
            if (in_array($m->type, ['adjustment', 'transfer_in', 'transfer_out']) && $adjustments->has($m->reference_id)) {
                $adj = $adjustments[$m->reference_id];
                $photo = $adj->photo_path ? route('kebun.stocks.photo', $adj->photo_path) : null;

                if ($adj->type === 'transfer') {
                    $details[$m->id] = [
                        'title' => 'Detail Transfer Stok',
                        'rows' => [
                            '__photo__' => $photo,
                            'Dari Kebun' => $adj->garden->name ?? '-',
                            'Ke Kebun' => $adj->toGarden->name ?? '-',
                            'Jumlah Dikirim' => $adj->physical_qty,
                            'Catatan' => $adj->reason ?? '-',
                            'Lokasi' => $adj->location_label ?? '-',
                            'Dilakukan Oleh' => $adj->adjuster->name ?? '-',
                        ],
                    ];
                } else {
                    $details[$m->id] = [
                        'title' => 'Detail Penyesuaian Stok',
                        'rows' => [
                            '__photo__' => $photo,
                            'Jenis' => $adjLabel[$adj->type] ?? ucfirst($adj->type),
                            'Kebun' => $adj->garden->name ?? '-',
                            'Stok Sebelum' => $adj->system_qty_before,
                            'Stok Fisik' => $adj->physical_qty,
                            'Selisih' => $adj->difference,
                            'Catatan' => $adj->reason ?? '-',
                            'Lokasi' => $adj->location_label ?? '-',
                            'Koordinat' => ($adj->latitude && $adj->longitude) ? $adj->latitude.', '.$adj->longitude : '-',
                            'Dilakukan Oleh' => $adj->adjuster->name ?? '-',
                        ],
                    ];
                }
            } elseif ($m->type === 'purchase' && $purchases->has($m->reference_id)) {
                $pur = $purchases[$m->reference_id];

                $details[$m->id] = [
                    'title' => 'Detail Pembelian',
                    'rows' => [
                        'Supplier' => $pur->supplier->name ?? '-',
                        'No. Invoice' => $pur->invoice_number ?? '-',
                        'Jumlah' => $pur->quantity,
                        'Total' => 'Rp'.number_format($pur->total_price, 0, ',', '.'),
                        'Diterima di' => $pur->receivedGarden->name ?? '-',
                        'Kebun Tujuan' => $pur->targetGarden->name ?? '-',
                        'Dicatat Oleh' => $pur->recorder->name ?? '-',
                    ],
                ];
            }
        }

        // Detail pemakaian
        $usages = \App\Models\KebutuhanKebun\Usage::with(['recorder', 'garden'])
            ->whereIn('id', $movements->where('type', 'usage')->pluck('reference_id'))
            ->get()->keyBy('id');

        foreach ($movements as $m) {
            if ($m->type === 'usage' && $usages->has($m->reference_id)) {
                $use = $usages[$m->reference_id];

                $details[$m->id] = [
                    'title' => 'Detail Pemakaian',
                    'rows' => [
                        'Lokasi Kebun' => $use->garden->name ?? '-',
                        'Jumlah' => $use->quantity,
                        'Tanggal' => \Carbon\Carbon::parse($use->usage_date)->format('d M Y'),
                        'Catatan' => $use->notes ?? '-',
                        'Dicatat Oleh' => $use->recorder->name ?? '-',
                    ],
                ];
            }
        }

        return $details;
    }
}