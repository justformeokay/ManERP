<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankReconciliation extends Model
{
    protected $fillable = [
        'bank_account_id', 'statement_date', 'statement_balance',
        'book_balance', 'difference', 'status', 'reconciled_by',
        'reconciled_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'statement_date'    => 'date',
            'statement_balance' => 'decimal:2',
            'book_balance'      => 'decimal:2',
            'difference'        => 'decimal:2',
            'reconciled_at'     => 'datetime',
        ];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class, 'reconciliation_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
