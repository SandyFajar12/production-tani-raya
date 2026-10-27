<?php

namespace App\Http\Controllers\PemakaianSolar;

use App\Http\Controllers\Controller;
use App\Models\Fleet;
use App\Models\Garden;
use App\Models\PemakaianSolar\FleetBalance;
use App\Models\PemakaianSolar\Movement;
use App\Models\PemakaianSolar\Stock;
use App\Models\PemakaianSolar\Trip;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TripController extends Controller
{
    public function index()
    {
        $query = Trip::with(['fleet', 'garden', 'supplier', 'recorder']);

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('fleet', fn ($f) => $f->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (request('fleet_id')) {
            $query->where('fleet_id', (int) request('fleet_id'));
        }

        if (request('date') === 'today') {
            $query->whereDate('trip_date', now()->toDateString());
        }

        $trips = $query->orderByDesc('trip_date')->orderByDesc('id')->paginate($this->perPage())->withQueryString();

        $allFleets = Fleet::orderBy('name')->get();
        $fleets = Fleet::where('status', '!=', 'nonaktif')->orderBy('name')->get();
        $gardens = Garden::where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->where('modul', 'pemakaian_solar')->orderBy('name')->get();
        $balanceData = FleetBalance::all()->mapWithKeys(fn ($b) => [$b->fleet_id => (float) $b->balance])->all();

        return view('pemakaian-solar.trips.index', compact('trips', 'allFleets', 'fleets', 'gardens', 'suppliers', 'balanceData'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fleet_id' => ['required', Rule::exists('fleets', 'id')->where(fn ($q) => $q->where('status', '!=', 'nonaktif'))],
            'trip_date' => ['required', 'date'],
            'entry_type' => ['required', 'in:purchase,usage'],
            'purchased_qty' => ['required_if:entry_type,purchase', 'nullable', 'numeric', 'min:0'],
            'stocked_qty' => ['required_if:entry_type,purchase', 'nullable', 'numeric', 'min:0'],
            'garden_id' => ['nullable', Rule::exists('gardens', 'id')->where('is_active', true)],
            'remaining_qty' => ['required', 'numeric', 'min:0'],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('is_active', true)->where('modul', 'pemakaian_solar')],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ], [
            'fleet_id.required' => 'Armada wajib dipilih.',
            'fleet_id.exists' => 'Armada tidak valid atau sudah nonaktif.',
            'garden_id.exists' => 'Kebun yang dipilih tidak valid atau sudah nonaktif.',
            'supplier_id.exists' => 'Supplier tidak valid, pilih supplier modul Pemakaian Solar.',
            'purchased_qty.numeric' => 'Solar dibeli harus berupa angka (liter).',
            'stocked_qty.numeric' => 'Solar di-stock harus berupa angka (liter).',
            'remaining_qty.required' => 'Sisa solar standby di armada wajib diisi (isi 0 kalau kosong).',
            'remaining_qty.numeric' => 'Sisa solar standby harus berupa angka (liter).',
        ]);

        $trip = DB::transaction(function () use ($data) {
            FleetBalance::firstOrCreate(['fleet_id' => $data['fleet_id']], ['balance' => 0]);
            $balance = FleetBalance::where('fleet_id', $data['fleet_id'])->lockForUpdate()->first();

            $opening = round((float) $balance->balance, 2);
            // "Pemakaian Saja": tidak ada pembelian dan tidak ada stok ke kebun
            $isUsage = ($data['entry_type'] === 'usage');
            $purchased = $isUsage ? 0.0 : round((float) ($data['purchased_qty'] ?? 0), 2);
            $stocked = $isUsage ? 0.0 : round((float) ($data['stocked_qty'] ?? 0), 2);
            $remaining = round((float) $data['remaining_qty'], 2);

            $available = round($opening + $purchased, 2);

            if ($stocked > $available) {
                throw ValidationException::withMessages([
                    'stocked_qty' => 'Solar di-stock ('.Stock::fmt($stocked).' L) melebihi solar yang tersedia di armada (awal '.Stock::fmt($opening).' + dibeli '.Stock::fmt($purchased).' = '.Stock::fmt($available).' L).',
                ]);
            }

            $used = round($available - $stocked - $remaining, 2);

            if ($used < 0 && $isUsage) {
                throw ValidationException::withMessages([
                    'remaining_qty' => 'Sisa standby ('.Stock::fmt($remaining).' L) lebih besar dari solar awal di armada ('.Stock::fmt($opening).' L). Tangki tidak bisa bertambah tanpa isi tangki atau pembelian: catat dulu lewat menu Isi Tangki, atau pakai Koreksi Saldo kalau saldo di sistem tidak sesuai dengan tangki.',
                ]);
            }

            if ($used < 0) {
                throw ValidationException::withMessages([
                    'remaining_qty' => 'Sisa standby ('.Stock::fmt($remaining).' L) lebih besar dari solar yang tersisa di armada ('.Stock::fmt(round($available - $stocked, 2)).' L). Periksa angka dibeli, di-stock, atau sisa.',
                ]);
            }

            if ($stocked > 0 && empty($data['garden_id'])) {
                throw ValidationException::withMessages(['garden_id' => 'Kebun tempat stok wajib dipilih kalau ada solar yang di-stock.']);
            }

            $unitPrice = (! $isUsage && isset($data['unit_price'])) ? (float) $data['unit_price'] : null;

            $trip = Trip::create([
                'fleet_id' => $data['fleet_id'],
                'garden_id' => $stocked > 0 ? $data['garden_id'] : null,
                'trip_date' => $data['trip_date'],
                'entry_type' => $data['entry_type'],
                'opening_qty' => $opening,
                'purchased_qty' => $purchased,
                'stocked_qty' => $stocked,
                'remaining_qty' => $remaining,
                'used_qty' => $used,
                'supplier_id' => $isUsage ? null : ($data['supplier_id'] ?? null),
                'unit_price' => $unitPrice,
                'total_price' => $unitPrice !== null ? round($purchased * $unitPrice, 2) : null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => auth()->id(),
            ]);

            $balance->balance = $remaining;
            $balance->save();

            if ($stocked > 0) {
                Movement::record((int) $data['garden_id'], 'trip_stock', Trip::class, $trip->id, $stocked);
            }

            FleetBalance::notifyIfLow((int) $data['fleet_id'], $opening, $remaining);

            return $trip;
        });

        return back()->with('status', 'Pemakaian solar tercatat. Pemakaian perjalanan: '.Stock::fmt($trip->used_qty).' L, sisa di armada: '.Stock::fmt($trip->remaining_qty).' L.');
    }
}