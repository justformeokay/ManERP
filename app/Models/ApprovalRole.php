<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalRole extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'level',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ───────────────────────────────────────────────

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'approval_role_user')
            ->withTimestamps();
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class);
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Helpers ─────────────────────────────────────────────────────

    public static function findBySlug(string $slug): ?static
    {
        return static::where('slug', $slug)->first();
    }
}
