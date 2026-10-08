<?php

namespace App\Http\Controllers;

use App\Models\Sparepart;
use App\Models\SparepartCategory;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Http\Request;

class SparepartController extends Controller
{
    public function index()
    {
        $query = Sparepart::with(['category', 'unit']);

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }
        
        if (request('low_stock')) {
            $query->whereColumn('current_stock', '<=', 'min_stock');
        }        

        $spareparts = $query->orderByDesc('updated_at')->paginate($this->perPage())->withQueryString();
        $categories = SparepartCategory::orderBy('name')->get();
        $units = Unit::orderBy('id')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        return view('spareparts.index', compact('spareparts', 'categories', 'units', 'suppliers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:spareparts,code'],
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'exists:sparepart_categories,id'],
            'dimension' => ['nullable', 'string', 'max:100'],
            'unit_id' => ['required', 'exists:units,id'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'standard_price' => ['nullable', 'numeric', 'min:0'],
            'main_supplier_id' => ['nullable', 'exists:suppliers,id'],
        ]);

        Sparepart::create($data);

        return back()->with('status', 'Sparepart baru tersimpan ke master data.');
    }

    public function update(Request $request, Sparepart $sparepart)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:spareparts,code,'.$sparepart->id],
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'exists:sparepart_categories,id'],
            'dimension' => ['nullable', 'string', 'max:100'],
            'unit_id' => ['required', 'exists:units,id'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'standard_price' => ['nullable', 'numeric', 'min:0'],
            'main_supplier_id' => ['nullable', 'exists:suppliers,id'],
        ]);

        $sparepart->update($data);

        return back()->with('status', 'Data sparepart diperbarui.');
    }

    public function destroy(Sparepart $sparepart)
    {
        $hasHistory = \App\Models\Preorder::where('sparepart_id', $sparepart->id)->exists()
            || \App\Models\Purchase::where('sparepart_id', $sparepart->id)->exists()
            || \App\Models\UsageRecord::where('sparepart_id', $sparepart->id)->exists()
            || \App\Models\StockAdjustment::where('sparepart_id', $sparepart->id)->exists();

        if ($hasHistory) {
            return back()->withErrors([
                'sparepart' => 'Sparepart "'.$sparepart->name.'" tidak bisa dihapus karena masih memiliki riwayat transaksi (pre-order/pembelian/pemakaian/penyesuaian stok).',
            ]);
        }

        $sparepart->delete();

        return back()->with('status', 'Sparepart dihapus dari master data.');
    }
}
