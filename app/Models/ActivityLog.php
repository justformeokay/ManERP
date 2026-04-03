<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'module',
        'action',
        'auditable_type',
        'auditable_id',
        'description',
        'old_data',
        'new_data',
        'changes',
        'ip_address',
        'user_agent',
        'session_id',
        'checksum',
        'created_at',
    ];

    protected $casts = [
        'old_data'   => 'array',
        'new_data'   => 'array',
        'changes'    => 'array',
        'created_at' => 'datetime',
    ];

    // ── Immutability: prevent update/delete on audit records ─────

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('Audit log records are immutable and cannot be modified.');
        });

        static::deleting(function () {
            throw new \RuntimeException('Audit log records are immutable and cannot be deleted.');
        });
    }

    // ── Relationships ───────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Polymorphic relation to the audited model.
     */
    public function auditable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('description', 'like', "%{$term}%")
              ->orWhere('module', 'like', "%{$term}%")
              ->orWhere('action', 'like', "%{$term}%")
              ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$term}%"));
        });
    }

    public static function modules(): array
    {
        return [
            'clients', 'projects', 'products', 'categories',
            'warehouses', 'suppliers', 'inventory', 'manufacturing',
            'sales', 'purchasing', 'settings', 'users',
            'finance', 'accounting',
        ];
    }

    public static function actions(): array
    {
        return [
            'create', 'update', 'delete',
            'confirm', 'cancel', 'deliver', 'receive',
            'produce', 'transfer', 'invoice',
            'post', 'reverse', 'void', 'close', 'reopen',
        ];
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeForModel($query, Model $model)
    {
        return $query->where('auditable_type', $model->getMorphClass())
                     ->where('auditable_id', $model->getKey());
    }
}
