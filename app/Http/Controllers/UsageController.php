<?php

namespace App\Http\Controllers;

use App\Models\Fleet;
use App\Models\Sparepart;
use App\Models\StockMovement;
use App\Models\UsageRecord;
use Illuminate\Http\Request;

class UsageController extends Controller
{
    public function index()
    {
        $usages = UsageRecord::with(['sparepart', 'fleet', 'recorder'])->latest()->paginate($this->perPage())->withQueryString();
        $spareparts = Sparepart::orderBy('name')->get();
        $fleets = Fleet::where('status', '!=', 'nonaktif')->orderBy('name')->get();

        return view('usages.index', compact('usages', 'spareparts', 'fleets'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sparepart_id' => ['required', 'exists:spareparts,id'],
            'fleet_id' => ['required', 'exists:fleets,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'usage_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['recorded_by'] = auth()->id();

        $usage = UsageRecord::create($data);

        StockMovement::record(
            $usage->sparepart_id,
            'usage',
            UsageRecord::class,
            $usage->id,
            -$usage->quantity
        );

        return back()->with('status', 'Pemakaian sparepart tercatat, stok otomatis berkurang.');
    }
}
