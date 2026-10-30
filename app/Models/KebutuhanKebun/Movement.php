<?php

namespace App\Models\KebutuhanKebun;

use App\Models\Garden;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Movement extends Model
{
    protected $table = 'kebun_movements';

    public $timestamps = false;

    protected $fillable = [
        'item_id', 'garden_id', 'type', 'reference_type', 'reference_id',
        'quantity_change', 'balance_after', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function garden()
    {
        return $this->belongsTo(Garden::class);
    }

    /**
     * Catat satu baris pergerakan stok di satu kebun, lalu update stok kebun itu
     * dan total stok di tabel kebun_items. Semua dalam satu transaksi database.
     */
    public static function record(int $itemId, string $type, string $referenceType, int $referenceId, int $quantityChange, int $gardenId): self
    {
        return DB::transaction(function () use ($itemId, $type, $referenceType, $referenceId, $quantityChange, $gardenId) {
            $item = Item::lockForUpdate()->findOrFail($itemId);

            GardenStock::firstOrCreate(
                ['item_id' => $itemId, 'garden_id' => $gardenId],
                ['quantity' => 0]
            );
            $stock = GardenStock::where('item_id', $itemId)
                ->where('garden_id', $gardenId)
                ->lockForUpdate()
                ->first();

            if ($stock->quantity + $quantityChange < 0) {
                throw new \DomainException('Stok di kebun ini tidak mencukupi.');
            }

            $before = (int) $stock->quantity;
            $stock->quantity += $quantityChange;
            $stock->save();

            $item->current_stock += $quantityChange;
            $item->save();

            // Stok baru melewati batas minimum di kebun ini: kabari Admin Pusat dan Owner (sekali per penurunan)
            if ($quantityChange < 0 && $before > $item->min_stock && $stock->quantity <= $item->min_stock) {
                NotificationService::lowStockKebun($item, $gardenId, (int) $stock->quantity);
            }

            return self::create([
                'item_id' => $itemId,
                'garden_id' => $gardenId,
                'type' => $type,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'quantity_change' => $quantityChange,
                'balance_after' => $stock->quantity,
                'created_at' => now(),
            ]);
        });
    }
}