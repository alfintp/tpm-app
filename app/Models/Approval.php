<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Approval extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'record_id',
        'approver_id',
        'decision',
        'notes',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function record()
    {
        return $this->belongsTo(MaintenanceRecord::class, 'record_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
