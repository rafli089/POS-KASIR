<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    const ROLE_CASHIER = 'CASHIER';
    const ROLE_MANAGER = 'MANAGER';
    const ROLE_ADMIN = 'ADMIN';

    const STATUS_ACTIVE = 'ACTIVE';

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'pin_hash',
        'role',
        'status',
    ];

    protected $hidden = [
        'pin_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'pin_hash' => 'hashed',
        ];
    }

    public function isCashier(): bool
    {
        return $this->role === self::ROLE_CASHIER;
    }

    public function canManage(): bool
    {
        return in_array($this->role, [self::ROLE_MANAGER, self::ROLE_ADMIN], true);
    }

    public function shifts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}