<?php

namespace App\Http\Controllers;

use App\Models\Fleet;
use Illuminate\Http\Request;

class FleetController extends Controller
{
    public function index()
    {
        $query = Fleet::query();

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $fleets = $query->orderByDesc('updated_at')->paginate($this->perPage())->withQueryString();

        return view('fleets.index', compact('fleets'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:fleets,code'],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'string', 'max:50'],
            'status' => ['required', 'in:aktif,maintenance,nonaktif'],
        ]);

        Fleet::create($data);

        return back()->with('status', 'Data armada tersimpan.');
    }

    public function update(Request $request, Fleet $fleet)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:fleets,code,'.$fleet->id],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'string', 'max:50'],
            'status' => ['required', 'in:aktif,maintenance,nonaktif'],
        ]);

        $fleet->update($data);

        return back()->with('status', 'Data armada diperbarui.');
    }

    public function destroy(Fleet $fleet)
    {
        $hasHistory = \App\Models\Preorder::where('fleet_id', $fleet->id)->exists()
            || \App\Models\UsageRecord::where('fleet_id', $fleet->id)->exists();

        if ($hasHistory) {
            return back()->withErrors([
                'fleet' => 'Armada "'.$fleet->name.'" tidak bisa dihapus karena masih memiliki riwayat transaksi (pre-order/pemakaian).',
            ]);
        }

        $fleet->delete();

        return back()->with('status', 'Data armada dihapus.');
    }
}
