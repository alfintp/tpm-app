<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'full_name',
        'email',
        'password',
        'role',
        'city',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = ['is_manager', 'can_approve'];

    public function roleRelation()
    {
        return $this->belongsTo(Role::class, 'role', 'name');
    }

    public function getIsManagerAttribute(): bool
    {
        return $this->roleRelation?->is_manager ?? Role::where('name', $this->role)->value('is_manager') ?? false;
    }

    public function getCanApproveAttribute(): bool
    {
        return $this->roleRelation?->can_approve ?? Role::where('name', $this->role)->value('can_approve') ?? false;
    }
}
