<?php

namespace App\Models\PemakaianSolar;

use App\Models\Garden;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $table = 'solar_stocks';

    protected $fillable = ['garden_id', 'quantity', 'min_stock'];

    public function garden()
    {
        return $this->belongsTo(Garden::class);
    }

    /** Format liter untuk tampilan: 35 -> "35", 12.5 -> "12,5", 1000 -> "1.000" */
    public static function fmt($value): string
    {
        $s = number_format((float) $value, 2, ',', '.');

        return rtrim(rtrim($s, '0'), ',');
    }
}