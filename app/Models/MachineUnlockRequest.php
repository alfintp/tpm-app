<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MachineUnlockRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'machine_id',
        'requested_by_id',
        'reason',
        'requested_period',
        'status',
        'approved_by_id',
        'approved_at',
        'approval_notes',
        'expires_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }
}
