<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SparepartCategory extends Model
{
    protected $fillable = ['name'];

    public function spareparts()
    {
        return $this->hasMany(Sparepart::class, 'category_id');
    }
}
