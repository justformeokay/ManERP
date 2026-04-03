<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalItem extends Model
{
    protected $fillable = ['journal_entry_id', 'account_id', 'debit', 'debit_base', 'credit', 'credit_base'];

    protected function casts(): array
    {
        return [
            'debit' => 'decimal:2',
            'debit_base' => 'decimal:2',
            'credit' => 'decimal:2',
            'credit_base' => 'decimal:2',
        ];
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
