<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalFlow extends Model
{
    protected $fillable = [
        'module',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ── Supported Modules ───────────────────────────────────────────

    public const MODULES = [
        'purchase_order' => 'Purchase Order',
        'invoice'        => 'Invoice',
        'supplier_bill'  => 'Supplier Bill',
        'payment'        => 'Payment',
        'sales_order'    => 'Sales Order',
    ];

    // ── Relationships ───────────────────────────────────────────────

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class)->orderBy('step_order');
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    // ── Helpers ─────────────────────────────────────────────────────

    /**
     * Get the flow for a specific module.
     */
    public static function forModule(string $module): ?static
    {
        return static::active()->forModule($module)->first();
    }

    /**
     * Get applicable steps based on amount.
     */
    public function getApplicableSteps(float $amount): \Illuminate\Database\Eloquent\Collection
    {
        return $this->steps()
            ->where(function ($query) use ($amount) {
                $query->where(function ($q) use ($amount) {
                    $q->where('min_amount', '<=', $amount)
                      ->orWhereNull('min_amount');
                })->where(function ($q) use ($amount) {
                    $q->where('max_amount', '>=', $amount)
                      ->orWhereNull('max_amount');
                });
            })
            ->orderBy('step_order')
            ->get();
    }

    /**
     * Check if amount requires approval.
     */
    public function requiresApproval(float $amount): bool
    {
        return $this->getApplicableSteps($amount)->isNotEmpty();
    }

    /**
     * Get module label.
     */
    public static function getModuleLabel(string $module): string
    {
        return self::MODULES[$module] ?? ucfirst(str_replace('_', ' ', $module));
    }
}
