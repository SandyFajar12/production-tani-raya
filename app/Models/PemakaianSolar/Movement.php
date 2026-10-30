<?php

namespace App\Models\PemakaianSolar;

use App\Models\Garden;
use Illuminate\Database\Eloquent\Model;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class Movement extends Model
{
    protected $table = 'solar_movements';

    public $timestamps = false;

    protected $fillable = [
        'garden_id', 'type', 'reference_type', 'reference_id',
        'quantity_change', 'balance_after', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function garden()
    {
        return $this->belongsTo(Garden::class);
    }

    /**
     * Catat satu baris pergerakan stok solar di satu kebun, lalu update stok kebun itu.
     * Semua dalam satu transaksi database.
     */
    public static function record(int $gardenId, string $type, string $referenceType, int $referenceId, $quantityChange): self
    {
        $quantityChange = round((float) $quantityChange, 2);

        return DB::transaction(function () use ($gardenId, $type, $referenceType, $referenceId, $quantityChange) {
            Stock::firstOrCreate(['garden_id' => $gardenId], ['quantity' => 0]);

            $stock = Stock::where('garden_id', $gardenId)->lockForUpdate()->first();

            $before = round((float) $stock->quantity, 2);
            $after = round($before + $quantityChange, 2);

            if ($after < 0) {
                throw new \DomainException('Stok solar di kebun ini tidak mencukupi.');
            }

            $stock->quantity = $after;
            $stock->save();

            // Stok melewati batas minimum di kebun ini: kabari Admin Pusat dan Owner (sekali per penurunan)
            $min = round((float) $stock->min_stock, 2);
            if ($quantityChange < 0 && $min > 0 && $before > $min && $after <= $min) {
                NotificationService::lowFuelStock($gardenId, $after, $min);
            }

            return self::create([
                'garden_id' => $gardenId,
                'type' => $type,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'quantity_change' => $quantityChange,
                'balance_after' => $after,
                'created_at' => now(),
            ]);
        });
    }
}