<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Garden extends Model
{
    protected $fillable = ['name', 'address', 'pic_user_id', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }
}