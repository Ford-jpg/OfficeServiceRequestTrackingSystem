<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department_id',
        'phone',
        'job_title',
        'is_active',
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_active;
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function requestedServices(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'requester_id');
    }

    public function assignedServices(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'assigned_to_user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(RequestComment::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(RequestAuditLog::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isServiceManager(): bool
    {
        return $this->role === 'service_manager';
    }

    public function isTechnician(): bool
    {
        return $this->role === 'technician';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    public function canUpdateStatus(): bool
    {
        return in_array($this->role, ['admin', 'service_manager', 'technician'], true);
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;

        return in_array($this->role, $roles, true);
    }

    public function isInDepartment(?int $departmentId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->department_id !== null && (int) $this->department_id === (int) $departmentId;
    }
}
