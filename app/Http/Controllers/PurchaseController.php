<?php

namespace App\Http\Controllers;

use App\Models\Preorder;
use App\Models\Purchase;
use App\Models\Sparepart;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index()
    {
        $query = Purchase::with(['sparepart', 'supplier', 'recorder']);

        if ($search = request('search')) {
            $query->whereHas('sparepart', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if (request('date') === 'today') {
            $query->whereDate('purchase_date', now()->toDateString());
        }

        $purchases = $query->latest()->paginate($this->perPage())->withQueryString();
        $spareparts = Sparepart::orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        $approvedPreorders = Preorder::with('sparepart')->where('status', 'approved')->get()->map(function ($po) {
            $alreadyPurchased = Purchase::where('preorder_id', $po->id)->sum('quantity');
            $po->remaining_quantity = max(0, $po->approved_quantity - $alreadyPurchased);

            return $po;
        })->filter(fn ($po) => $po->remaining_quantity > 0)->values();

        return view('purchases.index', compact('purchases', 'spareparts', 'suppliers', 'approvedPreorders'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'preorder_id' => ['nullable', 'exists:preorders,id'],
            'sparepart_id' => ['required', 'exists:spareparts,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
            'purchase_date' => ['required', 'date'],
        ]);

        $data['total_price'] = $data['quantity'] * $data['unit_price'];
        $data['recorded_by'] = auth()->id();

        $purchase = Purchase::create($data);

        StockMovement::record(
            $purchase->sparepart_id,
            'purchase',
            Purchase::class,
            $purchase->id,
            $purchase->quantity
        );

        if ($purchase->preorder_id) {
            $preorder = Preorder::find($purchase->preorder_id);

            if ($preorder && $preorder->approved_quantity !== null) {
                $totalPurchased = Purchase::where('preorder_id', $preorder->id)->sum('quantity');

                if ($totalPurchased >= $preorder->approved_quantity) {
                    $preorder->update(['status' => 'purchased']);
                }
            }
        }

        return back()->with('status', 'Transaksi pembelian tersimpan, stok otomatis bertambah.');
    }
}
