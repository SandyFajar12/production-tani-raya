<?php

namespace App\Http\Controllers;

use App\Models\Garden;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GardenController extends Controller
{
    public function index()
    {
        $this->authorizeMaster($this->currentModul(), 'garden', 'view');

        $query = Garden::with('pic');

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhereHas('pic', fn ($p) => $p->where('name', 'like', "%{$search}%"));
            });
        }

        $gardens = $query->orderByDesc('updated_at')->paginate($this->perPage())->withQueryString();

        // User aktif + user nonaktif yang masih jadi PIC (supaya saat Edit tidak kosong)
        $pics = User::where('is_active', true)
            ->orWhereIn('id', Garden::whereNotNull('pic_user_id')->select('pic_user_id'))
            ->orderBy('name')
            ->get();

        $backUrl = $this->modulBackUrl($this->currentModul());

        return view('master.gardens.index', compact('gardens', 'pics', 'backUrl'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:gardens,name'],
            'address' => ['nullable', 'string'],
            'pic_user_id' => ['nullable', Rule::exists('users', 'id')->where('is_active', true)],
            'is_active' => ['required', 'boolean'],
        ], [
            'name.required' => 'Nama kebun wajib diisi.',
            'name.unique' => 'Nama kebun sudah terdaftar.',
            'pic_user_id.exists' => 'PIC yang dipilih tidak valid atau sudah nonaktif.',
        ]);

        Garden::create($data);

        return back()->with('status', 'Data kebun tersimpan.');
    }

    public function update(Request $request, Garden $garden)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:gardens,name,'.$garden->id],
            'address' => ['nullable', 'string'],
            'pic_user_id' => ['nullable', Rule::exists('users', 'id')->where(
                fn ($q) => $q->where('is_active', true)->orWhere('id', $garden->pic_user_id)
            )],
            'is_active' => ['required', 'boolean'],
        ], [
            'name.required' => 'Nama kebun wajib diisi.',
            'name.unique' => 'Nama kebun sudah terdaftar.',
            'pic_user_id.exists' => 'PIC yang dipilih tidak valid atau sudah nonaktif.',
        ]);

        $garden->update($data);

        return back()->with('status', 'Data kebun diperbarui.');
    }

    public function destroy(Garden $garden)
    {
        // Tabel Kebutuhan Kebun tidak memakai foreign key, jadi dicek manual di sini
        $usedInKebun = \App\Models\KebutuhanKebun\GardenStock::where('garden_id', $garden->id)->exists()
            || \App\Models\KebutuhanKebun\Movement::where('garden_id', $garden->id)->exists()
            || \App\Models\KebutuhanKebun\Adjustment::where('garden_id', $garden->id)->orWhere('to_garden_id', $garden->id)->exists()
            || \App\Models\KebutuhanKebun\Preorder::where('garden_id', $garden->id)->exists()
            || \App\Models\KebutuhanKebun\Purchase::where('garden_id', $garden->id)->orWhere('target_garden_id', $garden->id)->exists()
            || \App\Models\KebutuhanKebun\Usage::where('garden_id', $garden->id)->exists()
            || \App\Models\PemakaianSolar\Movement::where('garden_id', $garden->id)->exists()
            || \App\Models\PemakaianSolar\Adjustment::where('garden_id', $garden->id)->orWhere('to_garden_id', $garden->id)->exists();

        if ($usedInKebun) {
            return back()->withErrors([
                'garden' => 'Kebun "'.$garden->name.'" tidak bisa dihapus karena sudah dipakai di transaksi. Nonaktifkan saja.',
            ]);
        }

        try {
            $garden->delete();
        } catch (QueryException $e) {
            // Kebun yang sudah dipakai di transaksi ditolak oleh database
            return back()->withErrors([
                'garden' => 'Kebun "'.$garden->name.'" tidak bisa dihapus karena sudah dipakai di transaksi. Nonaktifkan saja.',
            ]);
        }

        return back()->with('status', 'Data kebun dihapus.');
    }
}