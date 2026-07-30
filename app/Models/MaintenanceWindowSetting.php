<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MaintenanceWindowSetting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'days_before',
        'days_after',
        'alert_days_before',
    ];

    protected $casts = [
        'days_before' => 'integer',
        'days_after' => 'integer',
        'alert_days_before' => 'integer',
    ];

    /**
     * Get the current global maintenance window setting (singleton).
     * Creates a default row if none exists yet.
     */
    public static function current(): self
    {
        $setting = static::query()->first();
        if (!$setting) {
            $setting = static::create([
                'days_before' => 2,
                'days_after' => 0,
                'alert_days_before' => 7,
            ]);
        }
        return $setting;
    }
}
