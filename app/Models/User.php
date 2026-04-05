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
        'hr'            => 'HR & Payroll',
        'reports'       => 'Reports',
        'admin'         => 'Administration',
    ];

    public const PERMISSION_ACTIONS = ['view', 'create', 'edit', 'delete'];

    /**
     * Special granular permissions beyond standard CRUD.
     * These enable Segregation of Duties (SoD) for sensitive operations.
     */
    public const SPECIAL_PERMISSIONS = [
        'accounting.close_period'  => 'Close / Reopen Fiscal Periods',
        'accounting.post_gl'       => 'Post Transactions to General Ledger',
        'hr.approve_payroll'       => 'Approve Payroll Periods',
        'hr.post_payroll'          => 'Post Payroll to Accounting',
        'inventory.view_cost'      => 'View Cost Prices & Valuations',
        'admin.manage_users'       => 'Manage Users',
        'admin.manage_settings'    => 'Manage System Settings',
        'admin.view_audit_logs'    => 'View Audit Logs',
        'admin.maintenance'        => 'System Maintenance & Backups',
        'admin.manage_license'     => 'License Management',
        'admin.impersonate'        => 'Impersonate Other Users',
    ];

    /**
     * Industrial role templates — preset permission sets for common ERP roles.
     */
    public const ROLE_TEMPLATES = [
        'super_admin',
        'finance_manager',
        'accounting_staff',
        'production_manager',
        'warehouse_staff',
        'purchasing',
        'sales',
        'hr_payroll',
    ];

    protected $fillable = ['name', 'email', 'password', 'password_changed_at', 'role', 'permissions', 'phone', 'status', 'locale'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_changed_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
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
     * Get all available permissions as flat array (standard CRUD + special).
     */
    public static function allPermissions(): array
    {
        $perms = [];
        foreach (self::PERMISSION_MODULES as $module => $label) {
            foreach (self::PERMISSION_ACTIONS as $action) {
                $perms[] = "{$module}.{$action}";
            }
        }
        foreach (self::SPECIAL_PERMISSIONS as $perm => $label) {
            $perms[] = $perm;
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

    // ── Approval Roles ──────────────────────────────────────────────

    public function approvalRoles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ApprovalRole::class, 'approval_role_user')
            ->withTimestamps();
    }

    /**
     * Check if user has a specific approval role.
     */
    public function hasApprovalRole(int|string $roleIdOrSlug): bool
    {
        if ($this->isAdmin()) {
            return true; // Admins can approve anything
        }

        if (is_numeric($roleIdOrSlug)) {
            return $this->approvalRoles()->where('approval_roles.id', $roleIdOrSlug)->exists();
        }

        return $this->approvalRoles()->where('slug', $roleIdOrSlug)->exists();
    }

    /**
     * Get approval role IDs for this user.
     */
    public function getApprovalRoleIds(): array
    {
        return $this->approvalRoles()->pluck('approval_roles.id')->toArray();
    }
}
