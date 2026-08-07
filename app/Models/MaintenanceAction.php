<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MaintenanceAction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'record_id',
        'machine_component_id',
        'action_type',
        'condition_before_pct',
        'condition_after_pct',
        'description',
        'stock_id',
        'stock_qty_used',
    ];

    public function record()
    {
        return $this->belongsTo(MaintenanceRecord::class, 'record_id');
    }

    public function component()
    {
        return $this->belongsTo(MachineComponent::class, 'machine_component_id');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }

    public function indicatorValues()
    {
        return $this->hasMany(MaintenanceActionIndicator::class, 'maintenance_action_id');
    }
}
