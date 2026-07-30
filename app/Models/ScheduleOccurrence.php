<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ScheduleOccurrence extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'schedule_id',
        'machine_id',
        'period_year',
        'period_month',
        'due_date',
        'original_date',
        'is_shifted',
    ];

    protected $casts = [
        'due_date' => 'date',
        'original_date' => 'date',
        'is_shifted' => 'boolean',
        'period_year' => 'integer',
        'period_month' => 'integer',
    ];

    public function schedule()
    {
        return $this->belongsTo(MaintenanceSchedule::class, 'schedule_id');
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}
