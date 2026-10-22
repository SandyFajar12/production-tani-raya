<?php

namespace App\Http\Controllers\KebutuhanKebun;

use App\Http\Controllers\Controller;
use App\Models\Garden;
use App\Models\KebutuhanKebun\Adjustment;
use App\Models\KebutuhanKebun\GardenStock;
use App\Models\KebutuhanKebun\Item;
use App\Models\KebutuhanKebun\Movement;
use App\Models\KebutuhanKebun\Preorder;
use App\Models\KebutuhanKebun\Purchase;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PreorderController extends Controller
{
    /** Status pre-order yang menunggu tindakan user ini (Admin Pusat: Diajukan, Owner: Menunggu Owner) */
    public static function waitingStatuses($user): array
    {
        $statuses = [];

        if ($user->hasPermission('need_preorder.review')) {
            $statuses[] = 'pending';
        }
        if ($user->hasPermission('need_preorder.approve')) {
            $statuses[] = 'forwarded';
        }

        return $statuses;
    }

    public function index()
    {
        $query = Preorder::with(['item', 'requester', 'garden', 'approver', 'unit']);

        if ($search = request('search')) {
            $query->whereHas('item', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if (request('status') === 'waiting') {
            $query->whereIn('status', self::waitingStatuses(auth()->user()));
        }

        $preorders = $query->latest()->paginate($this->perPage())->withQueryString();
        $items = Item::orderBy('name')->get();
        $gardens = Garden::where('is_active', true)->orderBy('name')->get();

        $rows = $preorders->getCollection();

        // Riwayat pembelian per pre-order ("sudah dibeli X dari Y")
        $purchasedSums = Purchase::whereIn('preorder_id', $rows->pluck('id'))
            ->selectRaw('preorder_id, SUM(quantity) as total')
            ->groupBy('preorder_id')
            ->pluck('total', 'preorder_id');

        // Stok per kebun, untuk pilihan "Diambil dari" saat Stok Ada
        $stocksByItem = GardenStock::with('garden')
            ->whereIn('item_id', $rows->pluck('item_id')->unique())
            ->where('quantity', '>', 0)
            ->get()
            ->groupBy('item_id');

        return view('kebutuhan-kebun.preorders.index', compact('preorders', 'items', 'gardens', 'purchasedSums', 'stocksByItem'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:kebun_items,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'garden_id' => ['required', Rule::exists('gardens', 'id')->where('is_active', true)],
            'needed_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'string'],
        ], [
            'garden_id.required' => 'Kebun tujuan wajib dipilih.',
            'garden_id.exists' => 'Kebun tujuan tidak valid atau sudah nonaktif.',
        ]);

        $item = Item::findOrFail($data['item_id']);

        $newPreorder = Preorder::create([
            'code' => 'POK-'.str_pad((string) (Preorder::max('id') + 1), 4, '0', STR_PAD_LEFT),
            'item_id' => $data['item_id'],
            'quantity' => $data['quantity'],
            'unit_id' => $item->unit_id,
            'garden_id' => $data['garden_id'],
            'needed_date' => $data['needed_date'] ?? null,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
            'requested_by' => auth()->id(),
        ]);

        NotificationService::kebunPreorderCreated($newPreorder);

        return back()->with('status', 'Pengajuan pre-order terkirim ke Admin Pusat.');
    }

    /** Admin Pusat: stok ada, kirim dari stok ke kebun tujuan */
    public function ship(Request $request, Preorder $preorder)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'source_garden_id' => ['required', Rule::exists('gardens', 'id')->where('is_active', true)],
        ], [
            'quantity.required' => 'Jumlah yang dikirim wajib diisi.',
            'quantity.min' => 'Jumlah yang dikirim minimal 1.',
            'source_garden_id.required' => 'Kebun asal stok wajib dipilih.',
        ]);

        DB::transaction(function () use ($data, $preorder) {
            $po = Preorder::lockForUpdate()->findOrFail($preorder->id);

            if ($po->status !== 'pending') {
                throw ValidationException::withMessages(['preorder' => 'Pre-order ini sudah diproses.']);
            }
            if (! $po->garden_id) {
                throw ValidationException::withMessages(['preorder' => 'Pre-order ini belum punya kebun tujuan, teruskan ke Owner atau tolak.']);
            }
            if ((int) $data['source_garden_id'] === (int) $po->garden_id) {
                throw ValidationException::withMessages(['preorder' => 'Kebun asal tidak boleh sama dengan kebun tujuan.']);
            }
            if ($data['quantity'] > $po->quantity) {
                throw ValidationException::withMessages(['quantity' => 'Jumlah dikirim ('.$data['quantity'].') melebihi jumlah pengajuan ('.$po->quantity.').']);
            }

            $available = (int) GardenStock::where('item_id', $po->item_id)
                ->where('garden_id', $data['source_garden_id'])
                ->lockForUpdate()
                ->value('quantity');

            if ($data['quantity'] > $available) {
                throw ValidationException::withMessages(['quantity' => 'Jumlah dikirim ('.$data['quantity'].') melebihi stok di kebun asal ('.$available.').']);
            }

            $adjustment = Adjustment::create([
                'item_id' => $po->item_id,
                'garden_id' => $data['source_garden_id'],
                'to_garden_id' => $po->garden_id,
                'type' => 'transfer',
                'system_qty_before' => $available,
                'physical_qty' => $data['quantity'],
                'difference' => -$data['quantity'],
                'reason' => 'Pengiriman pre-order '.$po->code,
                'adjusted_by' => auth()->id(),
            ]);

            Movement::record($po->item_id, 'transfer_out', Adjustment::class, $adjustment->id, -$data['quantity'], (int) $data['source_garden_id']);
            Movement::record($po->item_id, 'transfer_in', Adjustment::class, $adjustment->id, $data['quantity'], (int) $po->garden_id);

            $po->update([
                'status' => 'shipped',
                'shipped_quantity' => $data['quantity'],
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_notes' => 'Dikirim dari stok',
            ]);
        });

        NotificationService::kebunPreorderShipped(Preorder::find($preorder->id));

        return back()->with('status', 'Pre-order '.$preorder->code.' dikirim dari stok.');
    }

    /** Admin Pusat: stok tidak ada, teruskan ke Owner */
    public function forward(Preorder $preorder)
    {
        DB::transaction(function () use ($preorder) {
            $po = Preorder::lockForUpdate()->findOrFail($preorder->id);

            if ($po->status !== 'pending') {
                throw ValidationException::withMessages(['preorder' => 'Pre-order ini sudah diproses.']);
            }

            $po->update([
                'status' => 'forwarded',
                'approval_notes' => 'Diteruskan ke Owner oleh '.auth()->user()->name.' (stok tidak ada)',
            ]);
        });

        NotificationService::kebunPreorderForwarded(Preorder::find($preorder->id));

        return back()->with('status', 'Pre-order '.$preorder->code.' diteruskan ke Owner.');
    }

    /** Owner: setujui pembelian (jumlah boleh lebih dari pengajuan, kelebihannya jadi stok) */
    public function approve(Request $request, Preorder $preorder)
    {
        $data = $request->validate([
            'approved_quantity' => ['required', 'integer', 'min:1'],
        ], [
            'approved_quantity.required' => 'Jumlah yang disetujui wajib diisi.',
            'approved_quantity.min' => 'Jumlah yang disetujui minimal 1.',
        ]);

        DB::transaction(function () use ($data, $preorder) {
            $po = Preorder::lockForUpdate()->findOrFail($preorder->id);

            if ($po->status !== 'forwarded') {
                throw ValidationException::withMessages(['preorder' => 'Pre-order ini tidak sedang menunggu persetujuan Owner.']);
            }

            $po->update([
                'status' => 'approved',
                'approved_quantity' => $data['approved_quantity'],
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        });

        NotificationService::kebunPreorderApproved(Preorder::find($preorder->id));

        return back()->with('status', 'Pre-order '.$preorder->code.' disetujui, jumlah: '.$data['approved_quantity'].'.');
    }

    /** Admin Pusat (status Diajukan) atau Owner (status Menunggu Owner) */
    public function reject(Request $request, Preorder $preorder)
    {
        $user = auth()->user();

        DB::transaction(function () use ($request, $preorder, $user) {
            $po = Preorder::lockForUpdate()->findOrFail($preorder->id);

            if ($po->status === 'pending') {
                abort_unless($user->hasPermission('need_preorder.review'), 403, 'Anda tidak punya izin menolak pre-order tahap ini.');
            } elseif ($po->status === 'forwarded') {
                abort_unless($user->hasPermission('need_preorder.approve'), 403, 'Anda tidak punya izin menolak pre-order tahap ini.');
            } else {
                throw ValidationException::withMessages(['preorder' => 'Pre-order ini sudah diproses.']);
            }

            $po->update([
                'status' => 'rejected',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'approval_notes' => $request->input('approval_notes'),
            ]);
        });

        NotificationService::kebunPreorderRejected(Preorder::find($preorder->id));

        return back()->with('status', 'Pre-order '.$preorder->code.' ditolak.');
    }
}