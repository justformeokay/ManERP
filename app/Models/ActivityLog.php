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
        'description',
        'old_data',
        'new_data',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
        ];
    }

    public static function actions(): array
    {
        return [
            'create', 'update', 'delete',
            'confirm', 'cancel', 'deliver', 'receive',
            'produce', 'transfer', 'invoice',
        ];
    }
}
