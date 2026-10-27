<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isResepsionis(): bool
    {
        return $this->role === 'resepsionis';
    }

    public function isPetugas(): bool
    {
        return $this->role === 'petugas';
    }

    public function assignedVisits(): HasMany
    {
        return $this->hasMany(Visit::class, 'assigned_officer_id');
    }

    public function dutySchedules(): HasMany
    {
        return $this->hasMany(DutySchedule::class, 'officer_id');
    }

    public function dutySwapsOriginal(): HasMany
    {
        return $this->hasMany(DutySwap::class, 'original_officer_id');
    }

    public function dutySwapsReplacement(): HasMany
    {
        return $this->hasMany(DutySwap::class, 'replacement_officer_id');
    }

    public function callEvents(): HasMany
    {
        return $this->hasMany(CallEvent::class, 'called_by');
    }
}
