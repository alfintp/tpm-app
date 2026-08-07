<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StockUsage extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'stock_id',
        'maintenance_action_id',
        'maintenance_record_id',
        'machine_id',
        'machine_component_id',
        'quantity_used',
        'used_at',
        'technician_id',
    ];

    protected $casts = [
        'quantity_used' => 'integer',
        'used_at' => 'date',
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class, 'machine_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function maintenanceRecord()
    {
        return $this->belongsTo(MaintenanceRecord::class, 'maintenance_record_id');
    }

    public function machineComponent()
    {
        return $this->belongsTo(MachineComponent::class, 'machine_component_id');
    }
}
