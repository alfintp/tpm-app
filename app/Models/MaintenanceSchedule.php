<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MaintenanceSchedule extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'machine_id',
        'interval_days',
        'schedule_type',
        'next_due_date',
        'is_active',
    ];

    protected $casts = [
        'next_due_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}
