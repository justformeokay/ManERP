<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_STAFF = 'staff';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    /**
     * Permission modules — each module supports: view, create, edit, delete
     */
    public const PERMISSION_MODULES = [
        'clients'       => 'CRM / Clients',
        'projects'      => 'Projects',
        'products'      => 'Products & Categories',
        'warehouses'    => 'Warehouses',
        'inventory'     => 'Inventory & Transfers',
        'suppliers'     => 'Suppliers',
        'sales'         => 'Sales Orders',
        'purchasing'    => 'Purchase Orders',
        'manufacturing' => 'Manufacturing',
        'finance'       => 'Finance (Invoices & Payments)',
        'accounting'    => 'Accounting',
        'reports'       => 'Reports',
    ];

    public const PERMISSION_ACTIONS = ['view', 'create', 'edit', 'delete'];

    protected $fillable = ['name', 'email', 'password', 'role', 'permissions', 'phone', 'status', 'locale'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if the user has a specific permission (e.g. "sales.view").
     * Admins always have all permissions.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return in_array($permission, $this->permissions ?? [], true);
    }

    /**
     * Check if user can access any action within a module.
     */
    public function hasModuleAccess(string $module): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        foreach (self::PERMISSION_ACTIONS as $action) {
            if ($this->hasPermission("{$module}.{$action}")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all available permissions as flat array.
     */
    public static function allPermissions(): array
    {
        $perms = [];
        foreach (self::PERMISSION_MODULES as $module => $label) {
            foreach (self::PERMISSION_ACTIONS as $action) {
                $perms[] = "{$module}.{$action}";
            }
        }
        return $perms;
    }

    public static function roleOptions(): array
    {
        return [self::ROLE_ADMIN, self::ROLE_STAFF];
    }

    public static function statusOptions(): array
    {
        return [self::STATUS_ACTIVE, self::STATUS_INACTIVE];
    }

    public function scopeSearch($query, ?string $term)
    {
        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%");
            });
        }
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
