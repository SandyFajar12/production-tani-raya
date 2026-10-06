<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Sparepart;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\UsageRecord;
use App\Services\LocationLookupService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index()
    {
        $spareparts = Sparepart::orderBy('name')->paginate($this->perPage())->withQueryString();

        return view('stocks.index', compact('spareparts'));
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'sparepart_id' => ['required', 'exists:spareparts,id'],
            'type' => ['required', 'in:opname,damaged_lost,return_to_supplier,other'],
            'physical_qty' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string'],
            'photo_data' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $sparepart = Sparepart::findOrFail($data['sparepart_id']);
        $before = $sparepart->current_stock;
        $difference = $data['physical_qty'] - $before;

        $photoPath = !empty($data['photo_data']) ? $this->savePhoto($data['photo_data']) : null;

        $locationLabel = null;
        if (!empty($data['latitude']) && !empty($data['longitude'])) {
            $locationLabel = app(LocationLookupService::class)->nearestLabel((float) $data['latitude'], (float) $data['longitude']);
        }

        $adjustment = StockAdjustment::create([
            'sparepart_id' => $sparepart->id,
            'type' => $data['type'],
            'system_qty_before' => $before,
            'physical_qty' => $data['physical_qty'],
            'difference' => $difference,
            'reason' => $data['reason'],
            'adjusted_by' => auth()->id(),
            'photo_path' => $photoPath,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'location_label' => $locationLabel,
        ]);

        StockMovement::record(
            $sparepart->id,
            'adjustment',
            StockAdjustment::class,
            $adjustment->id,
            $difference
        );

        return back()->with('status', 'Penyesuaian stok tersimpan.');
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

        $selectedId = $request->input('sparepart_id', $spareparts->first()->id ?? null);
        $selected = $selectedId ? Sparepart::find($selectedId) : null;

        $movements = $selected
            ? StockMovement::where('sparepart_id', $selected->id)->orderByDesc('id')->get()
            : collect();

        // Ambil data detail tiap jenis transaksi sekaligus (biar gak query satu-satu di view)
        $adjustmentIds = $movements->where('type', 'adjustment')->pluck('reference_id');
        $purchaseIds = $movements->where('type', 'purchase')->pluck('reference_id');
        $usageIds = $movements->where('type', 'usage')->pluck('reference_id');

        $adjustments = StockAdjustment::with('adjuster')->whereIn('id', $adjustmentIds)->get()->keyBy('id');
        $purchases = Purchase::with(['supplier', 'recorder'])->whereIn('id', $purchaseIds)->get()->keyBy('id');
        $usages = UsageRecord::with(['fleet', 'recorder'])->whereIn('id', $usageIds)->get()->keyBy('id');

        return view('stocks.ledger', compact('spareparts', 'selected', 'movements', 'adjustments', 'purchases', 'usages'));
    }
}
