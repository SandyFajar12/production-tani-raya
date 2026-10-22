<?php

namespace App\Http\Controllers\KebutuhanKebun;

use App\Http\Controllers\Controller;
use App\Models\Garden;
use App\Models\KebutuhanKebun\GardenStock;
use App\Models\KebutuhanKebun\Item;
use App\Models\KebutuhanKebun\Movement;
use App\Models\KebutuhanKebun\Usage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UsageController extends Controller
{
    public function index()
    {
        $query = Usage::with(['item.unit', 'recorder', 'garden']);

        if ($search = request('search')) {
            $query->whereHas('item', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if (request('date') === 'today') {
            $query->whereDate('usage_date', now()->toDateString());
        }

        $usages = $query->latest()->paginate($this->perPage())->withQueryString();
        $items = Item::with('gardenStocks')->orderBy('name')->get();
        $gardens = Garden::where('is_active', true)->orderBy('name')->get();

        return view('kebutuhan-kebun.usages.index', compact('usages', 'items', 'gardens'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:kebun_items,id'],
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
            $available = (int) GardenStock::where('item_id', $data['item_id'])
                ->where('garden_id', $data['garden_id'])
                ->lockForUpdate()
                ->value('quantity');

            if ($data['quantity'] > $available) {
                throw ValidationException::withMessages([
                    'quantity' => 'Jumlah pemakaian ('.$data['quantity'].') melebihi stok tersedia di kebun ini ('.$available.').',
                ]);
            }

            $usage = Usage::create($data);

            Movement::record(
                $usage->item_id,
                'usage',
                Usage::class,
                $usage->id,
                -$usage->quantity,
                (int) $usage->garden_id
            );
        });

        return back()->with('status', 'Pemakaian tercatat, stok otomatis berkurang.');
    }
}