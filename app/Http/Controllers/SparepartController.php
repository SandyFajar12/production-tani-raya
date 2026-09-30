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
        $spareparts = Sparepart::with(['category', 'unit'])->orderBy('name')->get();
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
        $sparepart->delete();

        return back()->with('status', 'Sparepart dihapus dari master data.');
    }
}
