<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $query = Supplier::query();

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->orderByDesc('updated_at')->paginate($this->perPage())->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        Supplier::create($data);

        return back()->with('status', 'Data supplier tersimpan.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $supplier->update($data);

        return back()->with('status', 'Data supplier diperbarui.');
    }

    public function destroy(Supplier $supplier)
    {
        $hasHistory = \App\Models\Purchase::where('supplier_id', $supplier->id)->exists();

        if ($hasHistory) {
            return back()->withErrors([
                'supplier' => 'Supplier "'.$supplier->name.'" tidak bisa dihapus karena masih memiliki riwayat transaksi pembelian.',
            ]);
        }

        $isMainSupplier = \App\Models\Sparepart::where('main_supplier_id', $supplier->id)->exists();

        if ($isMainSupplier) {
            return back()->withErrors([
                'supplier' => 'Supplier "'.$supplier->name.'" tidak bisa dihapus karena masih dipakai sebagai Supplier Utama di salah satu Master Sparepart.',
            ]);
        }

        $supplier->delete();

        return back()->with('status', 'Data supplier dihapus.');
    }
}
