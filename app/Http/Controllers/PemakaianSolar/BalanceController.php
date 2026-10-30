<?php

namespace App\Http\Controllers\PemakaianSolar;

use App\Http\Controllers\Controller;
use App\Models\Fleet;
use App\Models\PemakaianSolar\BalanceAdjustment;
use App\Models\PemakaianSolar\FleetBalance;
use App\Models\PemakaianSolar\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BalanceController extends Controller
{
    public function index()
    {
        $balances = FleetBalance::all()->keyBy('fleet_id');

        // Armada aktif (saldo 0 tetap tampil) + armada nonaktif yang masih punya saldo
        $fleets = Fleet::orderBy('name')->get()
            ->filter(fn ($f) => $f->status !== 'nonaktif' || (float) optional($balances->get($f->id))->balance > 0)
            ->values();

        // Dari kartu "Standby Rendah" di dashboard: hanya armada yang standby-nya di bawah batas minimum
        if (request('low')) {
            $fleets = $fleets->filter(function ($f) use ($balances) {
                $b = $balances->get($f->id);

                return $b && (float) $b->min_standby > 0 && (float) $b->balance < (float) $b->min_standby;
            })->values();
        }

        $formFleets = Fleet::where('status', '!=', 'nonaktif')->orderBy('name')->get();
        $balanceData = $balances->map(fn ($b) => (float) $b->balance)->all();

        $lastTrips = Trip::selectRaw('fleet_id, MAX(trip_date) as last_date')
            ->where('status', '!=', 'cancelled')
            ->groupBy('fleet_id')
            ->pluck('last_date', 'fleet_id');

        $history = BalanceAdjustment::with(['fleet', 'adjuster'])->latest('id')->limit(20)->get();
        $ongoing = Trip::where('status', 'ongoing')->pluck('trip_date', 'fleet_id');

        return view('pemakaian-solar.balances.index', compact('fleets', 'balances', 'formFleets', 'balanceData', 'lastTrips', 'history', 'ongoing'));
    }

    /** Atur solar standby minimum per armada (0 = notifikasi standby rendah nonaktif) */
    public function setMinimum(Request $request)
    {
        $data = $request->validate([
            'fleet_id' => ['required', 'exists:fleets,id'],
            'min_standby' => ['required', 'numeric', 'min:0'],
        ], [
            'fleet_id.required' => 'Armada wajib dipilih.',
            'min_standby.required' => 'Standby minimum wajib diisi (isi 0 untuk menonaktifkan).',
            'min_standby.numeric' => 'Standby minimum harus berupa angka (liter).',
        ]);

        $balance = FleetBalance::firstOrCreate(['fleet_id' => $data['fleet_id']], ['balance' => 0]);
        $balance->min_standby = round((float) $data['min_standby'], 2);
        $balance->save();

        return back()->with('status', 'Standby minimum armada tersimpan.');
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'fleet_id' => ['required', 'exists:fleets,id'],
            'balance' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string'],
        ], [
            'fleet_id.required' => 'Armada wajib dipilih.',
            'balance.required' => 'Saldo solar aktual wajib diisi.',
            'balance.numeric' => 'Saldo solar harus berupa angka (liter).',
            'reason.required' => 'Alasan koreksi wajib diisi.',
        ]);

        DB::transaction(function () use ($data) {
            FleetBalance::firstOrCreate(['fleet_id' => $data['fleet_id']], ['balance' => 0]);
            $balance = FleetBalance::where('fleet_id', $data['fleet_id'])->lockForUpdate()->first();

            if (Trip::where('fleet_id', $data['fleet_id'])->where('status', 'ongoing')->exists()) {
                throw ValidationException::withMessages([
                    'fleet_id' => 'Armada ini masih dalam perjalanan. Lengkapi atau batalkan catatan perjalanannya dulu di menu Pemakaian, baru koreksi saldo.',
                ]);
            }

            $before = round((float) $balance->balance, 2);
            $after = round((float) $data['balance'], 2);

            BalanceAdjustment::create([
                'fleet_id' => $data['fleet_id'],
                'balance_before' => $before,
                'balance_after' => $after,
                'difference' => round($after - $before, 2),
                'reason' => $data['reason'],
                'adjusted_by' => auth()->id(),
            ]);

            $balance->balance = $after;
            $balance->save();

            FleetBalance::notifyIfLow((int) $data['fleet_id'], $before, $after);
        });

        return back()->with('status', 'Saldo solar armada dikoreksi.');
    }
}