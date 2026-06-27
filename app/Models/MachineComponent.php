<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MachineComponent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'machine_id',
        'category',
        'name',
        'specification',
        'qty',
        'unit',
        'difficulty',
        'last_replaced_at',
        'last_condition_pct',
    ];

    protected $casts = [
        'last_replaced_at' => 'date',
        'last_condition_pct' => 'integer',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    protected static function booted()
    {
        static::saved(function ($component) {
            $component->updateMachineCondition();
        });

        static::deleted(function ($component) {
            $component->updateMachineCondition();
        });
    }

    public function updateMachineCondition()
    {
        $machine = $this->machine;
        if ($machine) {
            $avg = $machine->components()->avg('last_condition_pct');
            $machine->update(['condition_pct' => $avg ? round($avg, 1) : 0]);
        }
    }
}
