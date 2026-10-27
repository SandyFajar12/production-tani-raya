<?php

namespace App\Models\PemakaianSolar;

use App\Models\Fleet;
use App\Models\Garden;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $table = 'solar_trips';

    /** Jenis catatan: kunci disimpan di database, nama ini yang tampil di layar */
    public const TYPES = [
        'purchase' => 'Beli Solar & Pemakaian',
        'usage' => 'Pemakaian Saja',
    ];

    protected $fillable = [
        'fleet_id', 'garden_id', 'trip_date', 'entry_type', 'opening_qty', 'purchased_qty', 'stocked_qty',
        'remaining_qty', 'used_qty', 'supplier_id', 'unit_price', 'total_price', 'notes', 'recorded_by',
    ];

    public function fleet()
    {
        return $this->belongsTo(Fleet::class);
    }

    /** Kebun tempat solar di-stock */
    public function garden()
    {
        return $this->belongsTo(Garden::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}