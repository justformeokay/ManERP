<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNote extends Model
{
    protected $fillable = [
        'credit_note_number', 'invoice_id', 'client_id', 'date',
        'amount', 'tax_amount', 'total_amount', 'reason', 'notes',
        'status', 'journal_entry_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date'         => 'date',
            'amount'       => 'decimal:2',
            'tax_amount'   => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public static function generateNumber(): string
    {
        $latest = static::latest('id')->first();
        $next = $latest ? (int) substr($latest->credit_note_number, 3) + 1 : 1;
        return 'CN-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
