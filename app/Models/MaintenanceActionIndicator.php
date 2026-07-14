<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MaintenanceActionIndicator extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'maintenance_action_id',
        'component_indicator_id',
        'value',
    ];

    protected $casts = [
        'value' => 'boolean',
    ];

    public function action()
    {
        return $this->belongsTo(MaintenanceAction::class, 'maintenance_action_id');
    }

    public function indicator()
    {
        return $this->belongsTo(ComponentIndicator::class, 'component_indicator_id');
    }
}
