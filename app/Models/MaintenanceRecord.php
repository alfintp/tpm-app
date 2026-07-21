<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MaintenanceRecord extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'machine_id',
        'technician_id',
        'schedule_id',
        'scheduled_period_date',
        'is_unscheduled',
        'is_late',
        'maintenance_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'condition_before_pct',
        'condition_after_pct',
        'notes',
        'status',
        'approval_flow_snapshot',
    ];

    protected $casts = [
        'maintenance_date' => 'datetime',
        'scheduled_period_date' => 'date',
        'is_unscheduled' => 'boolean',
        'is_late' => 'boolean',
        'approval_flow_snapshot' => 'array',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function schedule()
    {
        return $this->belongsTo(MaintenanceSchedule::class, 'schedule_id');
    }

    public function actions()
    {
        return $this->hasMany(MaintenanceAction::class, 'record_id');
    }

    public function approvals()
    {
        return $this->hasMany(Approval::class, 'record_id')->orderBy('step_order');
    }

    public function latestApproval()
    {
        return $this->hasOne(Approval::class, 'record_id')->latest('step_order');
    }
}
