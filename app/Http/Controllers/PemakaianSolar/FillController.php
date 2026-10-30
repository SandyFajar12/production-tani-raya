<?php

namespace App\Http\Controllers\PemakaianSolar;

use App\Http\Controllers\Controller;
use App\Models\Fleet;
use App\Models\Garden;
use App\Models\PemakaianSolar\Fill;
use App\Models\PemakaianSolar\FleetBalance;
use App\Models\PemakaianSolar\Movement;
use App\Models\PemakaianSolar\Stock;
use App\Models\PemakaianSolar\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FillController extends Controller
{
    public function index()
    {
        $query = Fill::with(['fleet', 'garden', 'recorder']);

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('fleet', fn ($f) => $f->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                  ->orWhereHas('garden', fn ($g) => $g->where('name', 'like', "%{$search}%"))
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (request('fleet_id')) {
            $query->where('fleet_id', (int) request('fleet_id'));
        }

        if (request('date') === 'today') {
            $query->whereDate('fill_date', now()->toDateString());
        }

        $fills = $query->orderByDesc('fill_date')->orderByDesc('id')->paginate($this->perPage())->withQueryString();

        $allFleets = Fleet::orderBy('name')->get();
        $fleets = Fleet::where('status', '!=', 'nonaktif')->orderBy('name')->get();
        $gardens = Garden::where('is_active', true)->orderBy('name')->get();
        $stockData = Stock::all()->mapWithKeys(fn ($s) => [$s->garden_id => (float) $s->quantity])->all();
        $balanceData = FleetBalance::all()->mapWithKeys(fn ($b) => [$b->fleet_id => (float) $b->balance])->all();
        $ongoingData = Trip::where('status', 'ongoing')->get()
            ->mapWithKeys(fn ($t) => [$t->fleet_id => \Carbon\Carbon::parse($t->trip_date)->format('d/m/Y')])->all();

        return view('pemakaian-solar.fills.index', compact('fills', 'allFleets', 'fleets', 'gardens', 'stockData', 'balanceData', 'ongoingData'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fleet_id' => ['required', Rule::exists('fleets', 'id')->where(fn ($q) => $q->where('status', '!=', 'nonaktif'))],
            'garden_id' => ['required', Rule::exists('gardens', 'id')->where('is_active', true)],
            'fill_date' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
        ], [
            'fleet_id.required' => 'Armada wajib dipilih.',
            'fleet_id.exists' => 'Armada tidak valid atau sudah nonaktif.',
            'garden_id.required' => 'Kebun sumber solar wajib dipilih.',
            'garden_id.exists' => 'Kebun yang dipilih tidak valid atau sudah nonaktif.',
            'quantity.required' => 'Jumlah solar wajib diisi.',
            'quantity.numeric' => 'Jumlah solar harus berupa angka (liter).',
            'quantity.min' => 'Jumlah solar harus lebih dari 0 liter.',
        ]);

        $fill = DB::transaction(function () use ($data) {
            // Urutan kunci sama dengan pencatatan perjalanan: saldo armada dulu, lalu stok kebun
            FleetBalance::firstOrCreate(['fleet_id' => $data['fleet_id']], ['balance' => 0]);
            $balance = FleetBalance::where('fleet_id', $data['fleet_id'])->lockForUpdate()->first();

            $qty = round((float) $data['quantity'], 2);
            $stock = round((float) Stock::where('garden_id', $data['garden_id'])->lockForUpdate()->value('quantity'), 2);

            if ($qty > $stock) {
                throw ValidationException::withMessages([
                    'quantity' => 'Jumlah isi tangki ('.Stock::fmt($qty).' L) melebihi stok solar di kebun ini ('.Stock::fmt($stock).' L).',
                ]);
            }

            $before = round((float) $balance->balance, 2);
            $after = round($before + $qty, 2);

            // Kalau armada sedang dalam perjalanan, isi tangki ini ikut dihitung ke perjalanan itu
            $ongoingTrip = Trip::where('fleet_id', $data['fleet_id'])->where('status', 'ongoing')->first();

            $fill = Fill::create([
                'fleet_id' => $data['fleet_id'],
                'trip_id' => $ongoingTrip->id ?? null,
                'garden_id' => $data['garden_id'],
                'fill_date' => $data['fill_date'],
                'quantity' => $qty,
                'balance_before' => $before,
                'balance_after' => $after,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => auth()->id(),
            ]);

            Movement::record((int) $data['garden_id'], 'fill_vehicle', Fill::class, $fill->id, -$qty);

            $balance->balance = $after;
            $balance->save();

            return $fill;
        });

        return back()->with('status', 'Isi tangki tercatat. Saldo solar armada sekarang '.Stock::fmt($fill->balance_after).' L, stok kebun berkurang '.Stock::fmt($fill->quantity).' L.'.($fill->trip_id ? ' Isi tangki ini terhitung ke perjalanan yang sedang berjalan.' : ''));
    }
}