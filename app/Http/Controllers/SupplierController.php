<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index()
    {
        $modul = $this->currentModul();
        $this->authorizeMaster($modul, 'supplier', 'view');

        $query = Supplier::where('modul', $modul);

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->orderByDesc('updated_at')->paginate($this->perPage())->withQueryString();
        $modulLabel = self::MODUL_LIST[$modul];
        $backUrl = $this->modulBackUrl($modul);

        return view('master.suppliers.index', compact('suppliers', 'modul', 'modulLabel', 'backUrl'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'modul' => ['nullable', Rule::in(array_keys(self::MODUL_LIST))],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['modul'] = $data['modul'] ?? 'sparepart';
        $this->authorizeMaster($data['modul'], 'supplier', 'manage');

        Supplier::create($data);

        return back()->with('status', 'Data supplier tersimpan.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $this->validated($request);

        $this->authorizeMaster($supplier->modul, 'supplier', 'manage');

        // Modul supplier ditentukan saat dibuat dan tidak berubah lewat edit
        unset($data['modul']);

        $supplier->update($data);

        return back()->with('status', 'Data supplier diperbarui.');
    }

    public function destroy(Supplier $supplier)
    {
        $this->authorizeMaster($supplier->modul, 'supplier', 'manage');

        $hasHistory = \App\Models\Purchase::where('supplier_id', $supplier->id)->exists()
            || \App\Models\KebutuhanKebun\Purchase::where('supplier_id', $supplier->id)->exists()
            || \App\Models\PemakaianSolar\Trip::where('supplier_id', $supplier->id)->exists();

        if ($hasHistory) {
            return back()->withErrors([
                'supplier' => 'Supplier "'.$supplier->name.'" tidak bisa dihapus karena masih memiliki riwayat transaksi pembelian.',
            ]);
        }

        $isMainSupplier = \App\Models\Sparepart::where('main_supplier_id', $supplier->id)->exists()
            || \App\Models\KebutuhanKebun\Item::where('main_supplier_id', $supplier->id)->exists();

        if ($isMainSupplier) {
            return back()->withErrors([
                'supplier' => 'Supplier "'.$supplier->name.'" tidak bisa dihapus karena masih dipakai sebagai Supplier Utama di salah satu master (Sparepart / Kebutuhan Kebun).',
            ]);
        }

        $supplier->delete();

        return back()->with('status', 'Data supplier dihapus.');
    }
}