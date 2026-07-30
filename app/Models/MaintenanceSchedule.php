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

    protected static function booted()
    {
        static::saving(function ($schedule) {
            if ($schedule->next_due_date) {
                $date = \Carbon\Carbon::parse($schedule->next_due_date);
                if ($date->isSunday()) {
                    $schedule->next_due_date = $date->addDay()->toDateString();
                }
            }
        });
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function occurrences()
    {
        return $this->hasMany(ScheduleOccurrence::class, 'schedule_id');
    }
}
