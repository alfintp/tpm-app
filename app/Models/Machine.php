<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Machine extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'description',
        'condition_pct',
        'location',
        'status',
        'pic_mesin_id',
        'maintenance_duration',
        'maintenance_start_date',
    ];

    public function schedules()
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }

    public function records()
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    public function components()
    {
        return $this->hasMany(MachineComponent::class);
    }

    public function picMesin()
    {
        return $this->belongsTo(User::class, 'pic_mesin_id');
    }
}
