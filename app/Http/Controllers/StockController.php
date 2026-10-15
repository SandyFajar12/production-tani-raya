<?php

namespace App\Http\Controllers;

use App\Models\Garden;
use App\Models\GardenStock;
use App\Models\Preorder;
use App\Models\Purchase;
use App\Models\Sparepart;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\UsageRecord;
use App\Services\LocationLookupService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StockController extends Controller
{
    public function index()
    {
        // Satu baris per sparepart per kebun
        $query = GardenStock::with(['sparepart.unit', 'sparepart.category', 'sparepart.gardenStocks.garden', 'garden'])
            ->join('spareparts', 'spareparts.id', '=', 'garden_stocks.sparepart_id')
            ->join('gardens', 'gardens.id', '=', 'garden_stocks.garden_id')
            ->select('garden_stocks.*');

        $search = trim((string) request('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('spareparts.code', 'like', "%{$search}%")
                  ->orWhere('spareparts.name', 'like', "%{$search}%")
                  ->orWhere('gardens.name', 'like', "%{$search}%");
            });
        }

        if (request('garden_id')) {
            $query->where('garden_stocks.garden_id', (int) request('garden_id'));
        }

        if (request('low_stock')) {
            $query->whereColumn('garden_stocks.quantity', '<=', 'spareparts.min_stock');
        }

        $stocks = $query->orderBy('spareparts.name')->orderBy('gardens.name')
            ->paginate($this->perPage())->withQueryString();

        // Dropdown form: semua sparepart (bukan hanya yang tampil di halaman), beserta stok per kebun
        $formSpareparts = Sparepart::with('gardenStocks')->orderBy('name')->get();
        $gardens = Garden::where('is_active', true)->orderBy('name')->get();
        $allGardens = Garden::orderBy('name')->get();

        return view('stocks.index', compact('stocks', 'formSpareparts', 'gardens', 'allGardens'));
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'sparepart_id' => ['required', 'exists:spareparts,id'],
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
        if (!empty($data['latitude']) && !empty($data['longitude'])) {
            $locationLabel = app(LocationLookupService::class)->nearestLabel((float) $data['latitude'], (float) $data['longitude']);
        }

        DB::transaction(function () use ($data, $isTransfer, $locationLabel) {
            $sparepart = Sparepart::lockForUpdate()->findOrFail($data['sparepart_id']);
            $gardenId = (int) $data['garden_id'];
            $qty = (int) $data['physical_qty'];

            $before = (int) GardenStock::where('sparepart_id', $sparepart->id)
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

            $photoPath = !empty($data['photo_data']) ? $this->savePhoto($data['photo_data']) : null;

            $adjustment = StockAdjustment::create([
                'sparepart_id' => $sparepart->id,
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
                StockMovement::record($sparepart->id, 'transfer_out', StockAdjustment::class, $adjustment->id, -$qty, $gardenId);
                StockMovement::record($sparepart->id, 'transfer_in', StockAdjustment::class, $adjustment->id, $qty, (int) $data['to_garden_id']);
                $this->fulfillPreorders($sparepart->id, (int) $data['to_garden_id'], $qty);
            } else {
                StockMovement::record($sparepart->id, 'adjustment', StockAdjustment::class, $adjustment->id, $difference, $gardenId);
            }
        });

        return back()->with('status', $isTransfer ? 'Transfer stok tersimpan.' : 'Penyesuaian stok tersimpan.');
    }

    /**
     * Jumlah yang ditransfer dibagi ke pre-order berstatus "Dibeli" dengan sparepart dan kebun tujuan yang sama,
     * dimulai dari yang paling lama. Pre-order yang jumlahnya terpenuhi berubah jadi "Dikirim".
     */
    private function fulfillPreorders(int $sparepartId, int $toGardenId, int $qty): void
    {
        $open = Preorder::where('sparepart_id', $sparepartId)
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
                NotificationService::preorderShipped($po);
            }
        }
    }

    private function savePhoto(string $dataUrl): string
    {
        [, $content] = explode(',', $dataUrl, 2);
        $decoded = base64_decode($content);

        $filename = 'stok-'.now()->format('Ymd-His').'-'.uniqid().'.jpg';
        $directory = storage_path('app/public/stock-photos');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($directory.'/'.$filename, $decoded);

        return $filename;
    }

    public function photo(string $filename)
    {
        // Cegah path traversal — cuma boleh nama file polos, gak boleh ada folder/..
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
        $spareparts = Sparepart::orderBy('name')->get();
        $gardens = Garden::orderBy('name')->get();

        $selectedId = $request->input('sparepart_id', $spareparts->first()->id ?? null);
        $selected = $selectedId ? Sparepart::find($selectedId) : null;
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

        $movements = StockMovement::with('garden')
            ->where('sparepart_id', $selected->id ?? 0)
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

        $items = $movements->getCollection();

        // Ambil data detail tiap jenis transaksi sekaligus (biar gak query satu-satu di view)
        $adjustmentIds = $items->whereIn('type', ['adjustment', 'transfer_in', 'transfer_out'])->pluck('reference_id');
        $purchaseIds = $items->where('type', 'purchase')->pluck('reference_id');
        $usageIds = $items->where('type', 'usage')->pluck('reference_id');

        $adjustments = StockAdjustment::with(['adjuster', 'garden', 'toGarden'])->whereIn('id', $adjustmentIds)->get()->keyBy('id');
        $purchases = Purchase::with(['supplier', 'recorder', 'receivedGarden', 'targetGarden'])->whereIn('id', $purchaseIds)->get()->keyBy('id');
        $usages = UsageRecord::with(['fleet', 'recorder', 'garden'])->whereIn('id', $usageIds)->get()->keyBy('id');

        return view('stocks.ledger', compact('spareparts', 'gardens', 'selected', 'gardenId', 'movements', 'adjustments', 'purchases', 'usages'));
    }
}