<?php

namespace App\Http\Controllers;

use App\Models\Sparepart;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index()
    {
        $spareparts = Sparepart::orderBy('name')->get();

        return view('stocks.index', compact('spareparts'));
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'sparepart_id' => ['required', 'exists:spareparts,id'],
            'type' => ['required', 'in:opname,damaged_lost,return_to_supplier,other'],
            'physical_qty' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string'],
        ]);

        $sparepart = Sparepart::findOrFail($data['sparepart_id']);
        $before = $sparepart->current_stock;
        $difference = $data['physical_qty'] - $before;

        $adjustment = StockAdjustment::create([
            'sparepart_id' => $sparepart->id,
            'type' => $data['type'],
            'system_qty_before' => $before,
            'physical_qty' => $data['physical_qty'],
            'difference' => $difference,
            'reason' => $data['reason'],
            'adjusted_by' => auth()->id(),
        ]);

        StockMovement::record(
            $sparepart->id,
            'adjustment',
            StockAdjustment::class,
            $adjustment->id,
            $difference
        );

        return back()->with('status', 'Penyesuaian stok tersimpan.');
    }

    public function ledger(Request $request)
    {
        $spareparts = Sparepart::orderBy('name')->get();

        $selectedId = $request->input('sparepart_id', $spareparts->first()->id ?? null);
        $selected = $selectedId ? Sparepart::find($selectedId) : null;

        $movements = $selected
            ? StockMovement::where('sparepart_id', $selected->id)->orderByDesc('id')->get()
            : collect();

        return view('stocks.ledger', compact('spareparts', 'selected', 'movements'));
    }
}
