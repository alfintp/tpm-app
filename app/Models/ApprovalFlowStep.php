<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ApprovalFlowStep extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'role',
        'step_order',
        'is_active',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'is_active'  => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('step_order');
    }
}
