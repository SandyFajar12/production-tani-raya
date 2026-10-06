<?php

namespace App\Http\Controllers;

use App\Models\Fleet;
use App\Models\Preorder;
use App\Models\Sparepart;
use Illuminate\Http\Request;

class PreorderController extends Controller
{
    public function index()
    {
        $preorders = Preorder::with(['sparepart', 'fleet', 'requester'])->latest()->paginate($this->perPage())->withQueryString();
        $spareparts = Sparepart::orderBy('name')->get();
        $fleets = Fleet::where('status', '!=', 'nonaktif')->orderBy('name')->get();

        return view('preorders.index', compact('preorders', 'spareparts', 'fleets'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sparepart_id' => ['required', 'exists:spareparts,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'fleet_id' => ['nullable', 'exists:fleets,id'],
            'needed_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'string'],
        ]);

        $sparepart = Sparepart::findOrFail($data['sparepart_id']);

        Preorder::create([
            'code' => 'PO-'.str_pad((string) (Preorder::max('id') + 1), 4, '0', STR_PAD_LEFT),
            'sparepart_id' => $data['sparepart_id'],
            'quantity' => $data['quantity'],
            'unit_id' => $sparepart->unit_id,
            'fleet_id' => $data['fleet_id'] ?? null,
            'needed_date' => $data['needed_date'] ?? null,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
            'requested_by' => auth()->id(),
        ]);

        return back()->with('status', 'Pengajuan pre-order terkirim ke approver.');
    }

    public function approve(Preorder $preorder)
    {
        $preorder->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('status', 'Pre-order '.$preorder->code.' disetujui.');
    }

    public function reject(Request $request, Preorder $preorder)
    {
        $preorder->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $request->input('approval_notes'),
        ]);

        return back()->with('status', 'Pre-order '.$preorder->code.' ditolak.');
    }
}
