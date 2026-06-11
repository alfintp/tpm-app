<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Request;

class ActivityLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'user_fullname',
        'activity',
        'details',
        'ip_address'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper to log an activity easily.
     */
    public static function log($activity, $details = null, $user = null)
    {
        $currentUser = $user ?? auth('sanctum')->user();
        
        return self::create([
            'user_id' => $currentUser ? $currentUser->id : null,
            'user_fullname' => $currentUser ? $currentUser->full_name : 'System',
            'activity' => $activity,
            'details' => $details,
            'ip_address' => Request::ip()
        ]);
    }
}
