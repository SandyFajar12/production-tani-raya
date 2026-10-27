<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class StockMovement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sparepart_id', 'garden_id', 'type', 'reference_type', 'reference_id',
        'quantity_change', 'balance_after', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function sparepart()
    {
        return $this->belongsTo(Sparepart::class);
    }

    public function garden()
    {
        return $this->belongsTo(Garden::class);
    }

    /**
     * Catat satu baris pergerakan stok di satu kebun, lalu update stok kebun itu
     * dan total stok di tabel spareparts. Semua dalam satu transaksi database.
     */
    public static function record(int $sparepartId, string $type, string $referenceType, int $referenceId, int $quantityChange, int $gardenId): self
    {
        return DB::transaction(function () use ($sparepartId, $type, $referenceType, $referenceId, $quantityChange, $gardenId) {
            $sparepart = Sparepart::lockForUpdate()->findOrFail($sparepartId);

            GardenStock::firstOrCreate(
                ['sparepart_id' => $sparepartId, 'garden_id' => $gardenId],
                ['quantity' => 0]
            );
            $stock = GardenStock::where('sparepart_id', $sparepartId)
                ->where('garden_id', $gardenId)
                ->lockForUpdate()
                ->first();

            if ($stock->quantity + $quantityChange < 0) {
                throw new \DomainException('Stok di kebun ini tidak mencukupi.');
            }

            $before = (int) $stock->quantity;
            $stock->quantity += $quantityChange;
            $stock->save();

            $sparepart->current_stock += $quantityChange;
            $sparepart->save();

            // Stok baru melewati batas minimum di kebun ini: kabari Admin Pusat dan Owner (sekali per penurunan)
            if ($quantityChange < 0 && $before > $sparepart->min_stock && $stock->quantity <= $sparepart->min_stock) {
                NotificationService::lowStock($sparepart, $gardenId, (int) $stock->quantity);
            }

            return self::create([
                'sparepart_id' => $sparepartId,
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