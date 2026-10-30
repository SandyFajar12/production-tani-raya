<?php

namespace App\Http\Controllers;

use App\Models\Fleet;
use App\Models\Garden;
use App\Models\GardenStock;
use App\Models\Sparepart;
use App\Models\StockMovement;
use App\Models\UsageRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UsageController extends Controller
{
    public function index()
    {
        $query = UsageRecord::with(['sparepart', 'fleet', 'recorder', 'garden']);

        if ($search = request('search')) {
            $query->whereHas('sparepart', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if (request('date') === 'today') {
            $query->whereDate('usage_date', now()->toDateString());
        }

        $usages = $query->latest()->paginate($this->perPage())->withQueryString();
        $spareparts = Sparepart::with('gardenStocks')->orderBy('name')->get();
        $fleets = Fleet::where('status', '!=', 'nonaktif')->orderBy('name')->get();
        $gardens = Garden::where('is_active', true)->orderBy('name')->get();

        return view('stock-sparepart.usages.index', compact('usages', 'spareparts', 'fleets', 'gardens'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sparepart_id' => ['required', 'exists:spareparts,id'],
            'fleet_id' => ['required', 'exists:fleets,id'],
            'garden_id' => ['required', Rule::exists('gardens', 'id')->where('is_active', true)],
            'quantity' => ['required', 'integer', 'min:1'],
            'usage_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ], [
            'garden_id.required' => 'Lokasi kebun pemakaian wajib dipilih.',
            'garden_id.exists' => 'Kebun yang dipilih tidak valid atau sudah nonaktif.',
        ]);

        $data['recorded_by'] = auth()->id();

        DB::transaction(function () use ($data) {
            $available = (int) GardenStock::where('sparepart_id', $data['sparepart_id'])
                ->where('garden_id', $data['garden_id'])
                ->lockForUpdate()
                ->value('quantity');

            if ($data['quantity'] > $available) {
                throw ValidationException::withMessages([
                    'quantity' => 'Jumlah pemakaian ('.$data['quantity'].') melebihi stok tersedia di kebun ini ('.$available.').',
                ]);
            }

            $usage = UsageRecord::create($data);

            StockMovement::record(
                $usage->sparepart_id,
                'usage',
                UsageRecord::class,
                $usage->id,
                -$usage->quantity,
                (int) $usage->garden_id
            );
        });

        return back()->with('status', 'Pemakaian sparepart tercatat, stok otomatis berkurang.');
    }
}