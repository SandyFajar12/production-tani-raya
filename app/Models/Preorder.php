<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Preorder extends Model
{
    protected $fillable = [
        'code', 'sparepart_id', 'quantity', 'unit_id', 'fleet_id', 'needed_date',
        'reason', 'status', 'requested_by', 'approved_by', 'approved_at', 'approval_notes',
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

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
