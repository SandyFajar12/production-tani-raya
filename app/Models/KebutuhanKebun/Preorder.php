<?php

namespace App\Models\KebutuhanKebun;

use App\Models\Garden;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Preorder extends Model
{
    protected $table = 'kebun_preorders';

    protected $fillable = [
        'code', 'item_id', 'quantity', 'approved_quantity', 'shipped_quantity', 'unit_id', 'garden_id',
        'needed_date', 'reason', 'status', 'requested_by', 'approved_by', 'approved_at', 'approval_notes',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /** Kebun tujuan pengiriman */
    public function garden()
    {
        return $this->belongsTo(Garden::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'preorder_id');
    }
}