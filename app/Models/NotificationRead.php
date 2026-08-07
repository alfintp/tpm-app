<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class NotificationRead extends Model
{
    use HasUuids;

    protected $table = 'notification_reads';

    protected $fillable = [
        'notification_id',
        'user_id',
        'read_at',
    ];

    public function notification()
    {
        return $this->belongsTo(AppNotification::class, 'notification_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
