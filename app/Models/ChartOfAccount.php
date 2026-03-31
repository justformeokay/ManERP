<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    protected $fillable = ['code', 'name', 'type', 'parent_id', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ───────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    public function journalItems(): HasMany
    {
        return $this->hasMany(JournalItem::class, 'account_id');
    }

    // ── Helpers ─────────────────────────────────────────────────

    public static function typeOptions(): array
    {
        return ['asset', 'liability', 'equity', 'revenue', 'expense'];
    }

    public static function typeColors(): array
    {
        return [
            'asset'     => 'bg-blue-50 text-blue-700 ring-blue-300',
            'liability' => 'bg-red-50 text-red-700 ring-red-300',
            'equity'    => 'bg-purple-50 text-purple-700 ring-purple-300',
            'revenue'   => 'bg-green-50 text-green-700 ring-green-300',
            'expense'   => 'bg-amber-50 text-amber-700 ring-amber-300',
        ];
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('code', 'like', "%{$term}%")
              ->orWhere('name', 'like', "%{$term}%");
        });
    }
}
