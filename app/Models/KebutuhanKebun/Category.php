<?php

namespace App\Models\KebutuhanKebun;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'kebun_categories';

    protected $fillable = ['name'];

    public function items()
    {
        return $this->hasMany(Item::class, 'category_id');
    }
}