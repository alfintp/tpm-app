<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Role extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'display_name',
        'can_approve',
        'can_report',
        'is_manager',
        'can_approve_unlock',
        'can_add_data',
        'can_delete_data',
        'required_difficulties',
        'is_active',
    ];

    protected $casts = [
        'can_approve' => 'boolean',
        'can_report'  => 'boolean',
        'is_manager'  => 'boolean',
        'can_approve_unlock' => 'boolean',
        'can_add_data' => 'boolean',
        'can_delete_data' => 'boolean',
        'is_active'   => 'boolean',
        'required_difficulties' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCanApprove($query)
    {
        return $query->where('can_approve', true);
    }

    public function scopeCanReport($query)
    {
        return $query->where('can_report', true);
    }
}
