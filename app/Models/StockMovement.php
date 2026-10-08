<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sparepart_id', 'type', 'reference_type', 'reference_id',
        'quantity_change', 'balance_after', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function sparepart()
    {
        return $this->belongsTo(Sparepart::class);
    }

    /**
     * Catat satu baris pergerakan stok + update current_stock di tabel spareparts.
     * Dipanggil dari PurchaseController, UsageController, StockController.
     */
    public static function record(int $sparepartId, string $type, string $referenceType, int $referenceId, int $quantityChange): self
    {
        $sparepart = Sparepart::findOrFail($sparepartId);
        $sparepart->current_stock += $quantityChange;
        $sparepart->save();

        return self::create([
            'sparepart_id' => $sparepartId,
            'type' => $type,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'quantity_change' => $quantityChange,
            'balance_after' => $sparepart->current_stock,
            'created_at' => now(),
        ]);
    }
}
