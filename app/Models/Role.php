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
        'required_difficulties',
        'is_active',
    ];

    protected $casts = [
        'can_approve' => 'boolean',
        'can_report'  => 'boolean',
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
