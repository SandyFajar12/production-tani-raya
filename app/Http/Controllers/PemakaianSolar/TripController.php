<?php

namespace App\Http\Controllers\PemakaianSolar;

use App\Http\Controllers\Controller;
use App\Models\Fleet;
use App\Models\Garden;
use App\Models\PemakaianSolar\BalanceAdjustment;
use App\Models\PemakaianSolar\Fill;
use App\Models\PemakaianSolar\FleetBalance;
use App\Models\PemakaianSolar\Movement;
use App\Models\PemakaianSolar\Stock;
use App\Models\PemakaianSolar\Trip;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TripController extends Controller
{
    public function index()
    {
        $query = Trip::with(['fleet', 'garden', 'supplier', 'recorder', 'updater', 'canceller']);

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

        // Total isi tangki yang terjadi selama perjalanan yang masih berjalan
        $fillSums = Fill::whereIn('trip_id', $trips->getCollection()->pluck('id'))
            ->selectRaw('trip_id, SUM(quantity) as total')
            ->groupBy('trip_id')
            ->pluck('total', 'trip_id');

        // Catatan terakhir (belum dibatalkan) tiap armada: hanya ini yang boleh dibatalkan setelah selesai
        $latestIds = Trip::where('status', '!=', 'cancelled')
            ->selectRaw('fleet_id, MAX(id) as last_id')
            ->groupBy('fleet_id')
            ->pluck('last_id', 'fleet_id');

        $allFleets = Fleet::orderBy('name')->get();
        $fleets = Fleet::where('status', '!=', 'nonaktif')->orderBy('name')->get();
        $gardens = Garden::where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->where('modul', 'pemakaian_solar')->orderBy('name')->get();
        $balanceData = FleetBalance::all()->mapWithKeys(fn ($b) => [$b->fleet_id => (float) $b->balance])->all();
        $ongoingData = Trip::where('status', 'ongoing')->get()
            ->mapWithKeys(fn ($t) => [$t->fleet_id => Carbon::parse($t->trip_date)->format('d/m/Y')])->all();

        return view('pemakaian-solar.trips.index', compact(
            'trips', 'allFleets', 'fleets', 'gardens', 'suppliers', 'balanceData', 'ongoingData', 'fillSums', 'latestIds'
        ));
    }

    private function messages(): array
    {
        return [
            'fleet_id.required' => 'Armada wajib dipilih.',
            'fleet_id.exists' => 'Armada tidak valid atau sudah nonaktif.',
            'garden_id.exists' => 'Kebun yang dipilih tidak valid atau sudah nonaktif.',
            'supplier_id.exists' => 'Supplier tidak valid, pilih supplier modul Pemakaian Solar.',
            'purchased_qty.required' => 'Solar dibeli wajib diisi (isi 0 kalau tidak ada).',
            'purchased_qty.numeric' => 'Solar dibeli harus berupa angka (liter).',
            'stocked_qty.required' => 'Solar di-stock wajib diisi (isi 0 kalau tidak ada).',
            'stocked_qty.numeric' => 'Solar di-stock harus berupa angka (liter).',
            'remaining_qty.required' => 'Sisa solar standby di armada wajib diisi (isi 0 kalau kosong).',
            'remaining_qty.numeric' => 'Sisa solar standby harus berupa angka (liter).',
            'cancel_reason.required' => 'Alasan pembatalan wajib diisi.',
        ];
    }

    /** Catat perjalanan baru: langsung lengkap, atau "masih dalam perjalanan" (dicatat saat berangkat). */
    public function store(Request $request)
    {
        $inProgress = $request->boolean('in_progress');
        $needsPurchase = ! $inProgress && $request->input('entry_type') === 'purchase';

        $data = $request->validate([
            'fleet_id' => ['required', Rule::exists('fleets', 'id')->where(fn ($q) => $q->where('status', '!=', 'nonaktif'))],
            'trip_date' => ['required', 'date'],
            'entry_type' => ['required', 'in:purchase,usage'],
            'purchased_qty' => [Rule::requiredIf($needsPurchase), 'nullable', 'numeric', 'min:0'],
            'stocked_qty' => [Rule::requiredIf($needsPurchase), 'nullable', 'numeric', 'min:0'],
            'garden_id' => ['nullable', Rule::exists('gardens', 'id')->where('is_active', true)],
            'remaining_qty' => [Rule::requiredIf(! $inProgress), 'nullable', 'numeric', 'min:0'],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('is_active', true)->where('modul', 'pemakaian_solar')],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ], $this->messages());

        $trip = DB::transaction(function () use ($data, $inProgress) {
            FleetBalance::firstOrCreate(['fleet_id' => $data['fleet_id']], ['balance' => 0]);
            $balance = FleetBalance::where('fleet_id', $data['fleet_id'])->lockForUpdate()->first();

            $open = Trip::where('fleet_id', $data['fleet_id'])->where('status', 'ongoing')->first();
            if ($open) {
                throw ValidationException::withMessages([
                    'fleet_id' => 'Armada ini masih punya perjalanan yang belum selesai (berangkat '.Carbon::parse($open->trip_date)->format('d/m/Y').'). Lengkapi lewat tombol Edit di Riwayat, atau batalkan dulu.',
                ]);
            }

            $opening = round((float) $balance->balance, 2);

            // Dicatat saat berangkat: sisa, pembelian, dan stok menyusul lewat tombol Edit
            if ($inProgress) {
                return Trip::create([
                    'fleet_id' => $data['fleet_id'],
                    'garden_id' => null,
                    'trip_date' => $data['trip_date'],
                    'entry_type' => $data['entry_type'],
                    'status' => 'ongoing',
                    'opening_qty' => $opening,
                    'fill_qty' => 0,
                    'purchased_qty' => 0,
                    'stocked_qty' => 0,
                    'remaining_qty' => 0,
                    'used_qty' => 0,
                    'supplier_id' => null,
                    'unit_price' => null,
                    'total_price' => null,
                    'notes' => $data['notes'] ?? null,
                    'recorded_by' => auth()->id(),
                ]);
            }

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
                'status' => 'done',
                'opening_qty' => $opening,
                'fill_qty' => 0,
                'purchased_qty' => $purchased,
                'stocked_qty' => $stocked,
                'remaining_qty' => $remaining,
                'used_qty' => $used,
                'supplier_id' => $isUsage ? null : ($data['supplier_id'] ?? null),
                'unit_price' => $unitPrice,
                'total_price' => $unitPrice !== null ? round($purchased * $unitPrice, 2) : null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => auth()->id(),
                'completed_at' => now(),
            ]);

            $balance->balance = $remaining;
            $balance->save();

            if ($stocked > 0) {
                Movement::record((int) $data['garden_id'], 'trip_stock', Trip::class, $trip->id, $stocked);
            }

            FleetBalance::notifyIfLow((int) $data['fleet_id'], $opening, $remaining);

            return $trip;
        });

        if ($trip->status === 'ongoing') {
            return back()->with('status', 'Perjalanan tercatat sebagai "Masih dalam perjalanan". Setelah armada sampai, lengkapi sisa solar lewat tombol Edit di Riwayat.');
        }

        return back()->with('status', 'Pemakaian solar tercatat. Pemakaian perjalanan: '.Stock::fmt($trip->used_qty).' L, sisa di armada: '.Stock::fmt($trip->remaining_qty).' L.');
    }

    /** Lengkapi perjalanan yang masih berjalan (tombol Edit): isi sisa solar, dan data pembelian/stok kalau ada. */
    public function complete(Request $request, Trip $trip)
    {
        $needsPurchase = $trip->entry_type === 'purchase';

        $data = $request->validate([
            'purchased_qty' => [Rule::requiredIf($needsPurchase), 'nullable', 'numeric', 'min:0'],
            'stocked_qty' => [Rule::requiredIf($needsPurchase), 'nullable', 'numeric', 'min:0'],
            'garden_id' => ['nullable', Rule::exists('gardens', 'id')->where('is_active', true)],
            'remaining_qty' => ['required', 'numeric', 'min:0'],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('is_active', true)->where('modul', 'pemakaian_solar')],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ], $this->messages());

        DB::transaction(function () use ($data, $trip) {
            // Urutan kunci: saldo armada dulu, lalu catatan, lalu stok kebun
            FleetBalance::firstOrCreate(['fleet_id' => $trip->fleet_id], ['balance' => 0]);
            $balance = FleetBalance::where('fleet_id', $trip->fleet_id)->lockForUpdate()->first();
            $t = Trip::lockForUpdate()->findOrFail($trip->id);

            if ($t->status !== 'ongoing') {
                throw ValidationException::withMessages(['trip' => 'Catatan ini sudah tidak berstatus "Masih dalam perjalanan".']);
            }

            $isUsage = $t->entry_type === 'usage';
            $opening = round((float) $t->opening_qty, 2);
            $fills = round((float) Fill::where('trip_id', $t->id)->sum('quantity'), 2);
            $purchased = $isUsage ? 0.0 : round((float) ($data['purchased_qty'] ?? 0), 2);
            $stocked = $isUsage ? 0.0 : round((float) ($data['stocked_qty'] ?? 0), 2);
            $remaining = round((float) $data['remaining_qty'], 2);

            $available = round($opening + $fills + $purchased, 2);

            if ($stocked > $available) {
                throw ValidationException::withMessages([
                    'stocked_qty' => 'Solar di-stock ('.Stock::fmt($stocked).' L) melebihi solar yang tersedia di armada (berangkat '.Stock::fmt($opening).' + isi tangki '.Stock::fmt($fills).' + dibeli '.Stock::fmt($purchased).' = '.Stock::fmt($available).' L).',
                ]);
            }

            $used = round($available - $stocked - $remaining, 2);

            if ($used < 0) {
                throw ValidationException::withMessages([
                    'remaining_qty' => 'Sisa standby ('.Stock::fmt($remaining).' L) lebih besar dari solar yang tersisa di armada ('.Stock::fmt(round($available - $stocked, 2)).' L: berangkat '.Stock::fmt($opening).' + isi tangki '.Stock::fmt($fills).' + dibeli '.Stock::fmt($purchased).' − di-stock '.Stock::fmt($stocked).'). Periksa angkanya.',
                ]);
            }

            if ($stocked > 0 && empty($data['garden_id'])) {
                throw ValidationException::withMessages(['garden_id' => 'Kebun tempat stok wajib dipilih kalau ada solar yang di-stock.']);
            }

            $unitPrice = (! $isUsage && isset($data['unit_price'])) ? (float) $data['unit_price'] : null;

            $t->update([
                'garden_id' => $stocked > 0 ? $data['garden_id'] : null,
                'fill_qty' => $fills,
                'purchased_qty' => $purchased,
                'stocked_qty' => $stocked,
                'remaining_qty' => $remaining,
                'used_qty' => $used,
                'supplier_id' => $isUsage ? null : ($data['supplier_id'] ?? null),
                'unit_price' => $unitPrice,
                'total_price' => $unitPrice !== null ? round($purchased * $unitPrice, 2) : null,
                'notes' => $data['notes'] ?? null,
                'status' => 'done',
                'completed_at' => now(),
                'updated_by' => auth()->id(),
            ]);

            $balance->balance = $remaining;
            $balance->save();

            if ($stocked > 0) {
                Movement::record((int) $data['garden_id'], 'trip_stock', Trip::class, $t->id, $stocked);
            }

            FleetBalance::notifyIfLow((int) $t->fleet_id, round($opening + $fills, 2), $remaining);
        });

        $trip->refresh();

        return back()->with('status', 'Perjalanan dilengkapi. Pemakaian: '.Stock::fmt($trip->used_qty).' L, sisa di armada: '.Stock::fmt($trip->remaining_qty).' L.');
    }

    /** Batalkan catatan (data tidak dihapus, hanya diberi status Dibatalkan beserta alasan). */
    public function cancel(Request $request, Trip $trip)
    {
        $data = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:500'],
        ], $this->messages());

        DB::transaction(function () use ($data, $trip) {
            FleetBalance::firstOrCreate(['fleet_id' => $trip->fleet_id], ['balance' => 0]);
            $balance = FleetBalance::where('fleet_id', $trip->fleet_id)->lockForUpdate()->first();
            $t = Trip::lockForUpdate()->findOrFail($trip->id);

            if ($t->status === 'cancelled') {
                throw ValidationException::withMessages(['trip' => 'Catatan ini sudah dibatalkan.']);
            }

            // Catatan yang sudah selesai: stok kebun dan saldo armada dikembalikan, hanya jika ini catatan terakhir armada itu
            if ($t->status === 'done') {
                $this->assertLatest($t);

                $stocked = round((float) $t->stocked_qty, 2);
                if ($stocked > 0 && $t->garden_id) {
                    $stock = round((float) Stock::where('garden_id', $t->garden_id)->lockForUpdate()->value('quantity'), 2);

                    if ($stock < $stocked) {
                        throw ValidationException::withMessages([
                            'trip' => 'Catatan ini tidak bisa dibatalkan karena stok solar di kebun tujuan ('.Stock::fmt($stock).' L) sudah lebih kecil dari yang pernah dimasukkan ('.Stock::fmt($stocked).' L). Luruskan stok kebun dulu lewat penyesuaian.',
                        ]);
                    }

                    Movement::record((int) $t->garden_id, 'trip_cancel', Trip::class, $t->id, -$stocked);
                }

                // Saldo armada kembali ke kondisi sebelum perjalanan (termasuk isi tangki selama perjalanan)
                $balance->balance = round((float) $t->opening_qty + (float) $t->fill_qty, 2);
                $balance->save();
            }

            $t->update([
                'status' => 'cancelled',
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
                'cancel_reason' => $data['cancel_reason'],
                'updated_by' => auth()->id(),
            ]);
        });

        return back()->with('status', 'Catatan dibatalkan.');
    }

    /** Catatan selesai hanya boleh dibatalkan kalau belum ada kejadian lain sesudahnya untuk armada yang sama. */
    private function assertLatest(Trip $t): void
    {
        $since = $t->completed_at ?? $t->created_at;

        $laterTrip = Trip::where('fleet_id', $t->fleet_id)->where('status', '!=', 'cancelled')->where('id', '>', $t->id)->exists();
        $laterFill = Fill::where('fleet_id', $t->fleet_id)->where('created_at', '>', $since)->exists();
        $laterAdjustment = BalanceAdjustment::where('fleet_id', $t->fleet_id)->where('created_at', '>', $since)->exists();

        if ($laterTrip || $laterFill || $laterAdjustment) {
            throw ValidationException::withMessages([
                'trip' => 'Catatan ini tidak bisa dibatalkan karena sudah ada catatan perjalanan, isi tangki, atau koreksi saldo sesudahnya untuk armada yang sama. Pakai Koreksi Saldo untuk meluruskan.',
            ]);
        }
    }
}