<?php

namespace App\Http\Controllers;

use App\Models\Fleet;
use Illuminate\Http\Request;

class FleetController extends Controller
{
    public function index()
    {
        $fleets = Fleet::orderBy('name')->get();

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
        $fleet->delete();

        return back()->with('status', 'Data armada dihapus.');
    }
}
