<?php

namespace App\Http\Controllers\KebutuhanKebun;

use App\Http\Controllers\Controller;
use App\Models\KebutuhanKebun\Category;
use App\Models\KebutuhanKebun\Item;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function index()
    {
        $query = Item::with(['category', 'unit', 'mainSupplier', 'gardenStocks.garden']);

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $items = $query->orderByDesc('updated_at')->paginate($this->perPage())->withQueryString();
        $categories = Category::orderBy('name')->get();
        $units = Unit::orderBy('id')->get();
        $suppliers = Supplier::where('is_active', true)->where('modul', 'kebutuhan_kebun')->orderBy('name')->get();

        return view('kebutuhan-kebun.items.index', compact('items', 'categories', 'units', 'suppliers'));
    }

    private function rules(?Item $item = null): array
    {
        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('kebun_items', 'code')->ignore($item ? $item->id : null)],
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'exists:kebun_categories,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'standard_price' => ['nullable', 'numeric', 'min:0'],
            'main_supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('modul', 'kebutuhan_kebun')],
        ];
    }

    private function messages(): array
    {
        return [
            'code.unique' => 'Kode item sudah terdaftar.',
            'main_supplier_id.exists' => 'Supplier utama tidak valid, pilih supplier modul Kebutuhan Kebun.',
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->messages());

        Item::create($data);

        return back()->with('status', 'Item baru tersimpan ke master kebutuhan kebun.');
    }

    public function update(Request $request, Item $item)
    {
        $data = $request->validate($this->rules($item), $this->messages());

        $item->update($data);

        return back()->with('status', 'Data item diperbarui.');
    }

    public function destroy(Item $item)
    {
        $hasHistory = \App\Models\KebutuhanKebun\Movement::where('item_id', $item->id)->exists()
            || \App\Models\KebutuhanKebun\Adjustment::where('item_id', $item->id)->exists()
            || \App\Models\KebutuhanKebun\Preorder::where('item_id', $item->id)->exists()
            || \App\Models\KebutuhanKebun\Purchase::where('item_id', $item->id)->exists()
            || \App\Models\KebutuhanKebun\Usage::where('item_id', $item->id)->exists();

        if ($hasHistory) {
            return back()->withErrors([
                'item' => 'Item "'.$item->name.'" tidak bisa dihapus karena masih memiliki riwayat stok/transaksi.',
            ]);
        }

        $item->delete();

        return back()->with('status', 'Item dihapus dari master data.');
    }
}