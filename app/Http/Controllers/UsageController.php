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
        $query = UsageRecord::with(['sparepart', 'fleet', 'recorder']);

        if ($search = request('search')) {
            $query->whereHas('sparepart', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }
        
        if (request('date') === 'today') {
            $query->whereDate('usage_date', now()->toDateString());
        }        

        $usages = $query->latest()->paginate($this->perPage())->withQueryString();
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

        $sparepart = Sparepart::findOrFail($data['sparepart_id']);

        if ($data['quantity'] > $sparepart->current_stock) {
            return back()->withErrors([
                'quantity' => 'Jumlah pemakaian ('.$data['quantity'].') melebihi stok tersedia ('.$sparepart->current_stock.').',
            ])->withInput();
        }

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
