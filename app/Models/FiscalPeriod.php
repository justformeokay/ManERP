<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalPeriod extends Model
{
    protected $fillable = [
        'name', 'start_date', 'end_date', 'status',
        'closed_by', 'closed_at', 'closing_notes', 'closing_journal_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'closed_at'  => 'datetime',
        ];
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function closingJournal(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'closing_journal_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function containsDate(string $date): bool
    {
        return $date >= $this->start_date->toDateString()
            && $date <= $this->end_date->toDateString();
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeContainingDate($query, string $date)
    {
        return $query->where('start_date', '<=', $date)->where('end_date', '>=', $date);
    }
}
