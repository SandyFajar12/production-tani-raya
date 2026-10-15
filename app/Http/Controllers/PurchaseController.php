<?php

namespace App\Http\Controllers;

use App\Models\Garden;
use App\Models\Preorder;
use App\Models\Purchase;
use App\Models\Sparepart;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseController extends Controller
{
    public function index()
    {
        $query = Purchase::with(['sparepart', 'supplier', 'recorder', 'receivedGarden', 'targetGarden', 'preorder']);

        if ($search = request('search')) {
            $query->whereHas('sparepart', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if (request('date') === 'today') {
            $query->whereDate('purchase_date', now()->toDateString());
        }

        $purchases = $query->latest()->paginate($this->perPage())->withQueryString();
        $spareparts = Sparepart::orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $gardens = Garden::where('is_active', true)->orderBy('name')->get();

        // Hanya pre-order yang sudah disetujui Owner dan masih ada sisa
        $approvedPreorders = Preorder::with(['sparepart', 'garden'])->where('status', 'approved')->get()->map(function ($po) {
            $alreadyPurchased = Purchase::where('preorder_id', $po->id)->sum('quantity');
            $po->remaining_quantity = max(0, $po->approved_quantity - $alreadyPurchased);

            return $po;
        })->filter(fn ($po) => $po->remaining_quantity > 0)->values();

        return view('stock-sparepart.purchases.index', compact('purchases', 'spareparts', 'suppliers', 'gardens', 'approvedPreorders'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'preorder_id' => ['nullable', 'exists:preorders,id'],
            'sparepart_id' => ['required', 'exists:spareparts,id'],
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('is_active', true)],
            'garden_id' => ['required', Rule::exists('gardens', 'id')->where('is_active', true)],
            'target_garden_id' => ['nullable', Rule::exists('gardens', 'id')->where('is_active', true)],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
            'purchase_date' => ['required', 'date'],
        ], [
            'supplier_id.exists' => 'Supplier yang dipilih sudah nonaktif, pilih supplier lain.',
            'garden_id.required' => 'Lokasi "Diterima di" wajib dipilih.',
            'garden_id.exists' => 'Lokasi "Diterima di" tidak valid atau sudah nonaktif.',
            'target_garden_id.exists' => 'Kebun Tujuan tidak valid atau sudah nonaktif.',
        ]);

        $data['total_price'] = $data['quantity'] * $data['unit_price'];
        $data['recorded_by'] = auth()->id();

        DB::transaction(function () use (&$data) {
            $preorder = null;

            if (! empty($data['preorder_id'])) {
                // Kunci baris pre-order supaya dua orang yang mencatat bersamaan tidak bisa membeli dobel
                $preorder = Preorder::lockForUpdate()->findOrFail($data['preorder_id']);

                if ($preorder->status !== 'approved') {
                    throw ValidationException::withMessages(['preorder_id' => 'Pre-order ini belum disetujui Owner, atau sudah selesai dibeli.']);
                }
                if ((int) $preorder->sparepart_id !== (int) $data['sparepart_id']) {
                    throw ValidationException::withMessages(['sparepart_id' => 'Sparepart harus sama dengan sparepart di pre-order.']);
                }

                $remaining = $preorder->approved_quantity - (int) Purchase::where('preorder_id', $preorder->id)->sum('quantity');
                if ($data['quantity'] > $remaining) {
                    throw ValidationException::withMessages(['quantity' => 'Jumlah beli ('.$data['quantity'].') melebihi sisa yang disetujui ('.$remaining.').']);
                }

                // Kebun Tujuan otomatis dari pre-order
                if ($preorder->garden_id) {
                    $data['target_garden_id'] = $preorder->garden_id;
                }
            }

            if (empty($data['target_garden_id'])) {
                throw ValidationException::withMessages(['target_garden_id' => 'Kebun Tujuan wajib dipilih.']);
            }

            $purchase = Purchase::create($data);

            // Stok bertambah di lokasi "Diterima di"
            StockMovement::record(
                $purchase->sparepart_id,
                'purchase',
                Purchase::class,
                $purchase->id,
                $purchase->quantity,
                (int) $purchase->garden_id
            );

            if ($preorder) {
                $totalPurchased = (int) Purchase::where('preorder_id', $preorder->id)->sum('quantity');

                if ($totalPurchased >= $preorder->approved_quantity) {
                    $preorder->update(['status' => 'purchased']);
                }
            }
        });

        return back()->with('status', 'Transaksi pembelian tersimpan, stok otomatis bertambah.');
    }
}