<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Preorder extends Model
{
    protected $fillable = [
        'code', 'sparepart_id', 'quantity', 'approved_quantity', 'shipped_quantity', 'unit_id', 'fleet_id', 'garden_id',
        'needed_date', 'reason', 'status', 'requested_by', 'approved_by', 'approved_at', 'approval_notes',
    ];

    public function sparepart()
    {
        return $this->belongsTo(Sparepart::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function fleet()
    {
        return $this->belongsTo(Fleet::class);
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
        return $this->hasMany(Purchase::class);
    }
}