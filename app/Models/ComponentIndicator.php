<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ComponentIndicator extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'machine_component_id',
        'name',
        'description',
        'sort_order',
    ];

    public function component()
    {
        return $this->belongsTo(MachineComponent::class, 'machine_component_id');
    }

    public function actionValues()
    {
        return $this->hasMany(MaintenanceActionIndicator::class, 'component_indicator_id');
    }
}
