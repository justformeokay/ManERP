<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalTemplate extends Model
{
    protected $fillable = ['name', 'description', 'items', 'is_active', 'created_by',
        'is_recurring', 'frequency', 'next_run_date', 'last_run_date', 'end_date'];

    protected function casts(): array
    {
        return [
            'items'         => 'array',
            'is_active'     => 'boolean',
            'is_recurring'  => 'boolean',
            'next_run_date' => 'date',
            'last_run_date' => 'date',
            'end_date'      => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRecurringDue($query)
    {
        return $query->where('is_recurring', true)
            ->where('is_active', true)
            ->where('next_run_date', '<=', now()->toDateString())
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString()));
    }

    public static function frequencyOptions(): array
    {
        return ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'];
    }
}
