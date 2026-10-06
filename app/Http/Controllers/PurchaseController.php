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
        $purchases = Purchase::with(['sparepart', 'supplier', 'recorder'])->latest()->paginate($this->perPage())->withQueryString();
        $spareparts = Sparepart::orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $approvedPreorders = Preorder::with('sparepart')->where('status', 'approved')->get();

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

        return back()->with('status', 'Transaksi pembelian tersimpan, stok otomatis bertambah.');
    }
}
