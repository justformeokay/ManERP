<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    protected $fillable = [
        'reference', 'date', 'description', 'is_posted',
        'entry_type', 'cash_flow_category', 'reversed_entry_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_posted' => 'boolean',
        ];
    }

    public function reversedEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversed_entry_id');
    }

    public function reversingEntry(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'reversed_entry_id');
    }

    public static function entryTypeOptions(): array
    {
        return ['manual', 'auto', 'adjusting', 'closing', 'reversing'];
    }

    protected static function booted(): void
    {
        static::creating(function (JournalEntry $entry) {
            if (empty($entry->created_by)) {
                $entry->created_by = auth()->id();
            }
        });
    }

    // ── Relationships ───────────────────────────────────────────

    public function items(): HasMany
    {
        return $this->hasMany(JournalItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Helpers ─────────────────────────────────────────────────

    public function getTotalDebitAttribute(): float
    {
        return round((float) $this->items->sum('debit'), 2);
    }

    public function getTotalCreditAttribute(): float
    {
        return round((float) $this->items->sum('credit'), 2);
    }

    public function getIsBalancedAttribute(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.01;
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('reference', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }
}
