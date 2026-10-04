<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'username', 'staff_no', 'name', 'email', 'phone', 'unit_code', 'password',
        'is_active', 'must_change_password', 'last_login_at', 'disabled_at',
        'disabled_by', 'disable_reason',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime', 'password' => 'hashed', 'is_active' => 'boolean',
            'must_change_password' => 'boolean', 'last_login_at' => 'datetime', 'disabled_at' => 'datetime',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string ...$roles): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(fn (Role $role) => in_array($role->name, $roles, true) && $role->is_active);
        }

        return $this->roles()->whereIn('name', $roles)->where('is_active', true)->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('system_admin', 'himu_admin');
    }
}
