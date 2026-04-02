<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetLine extends Model
{
    protected $fillable = [
        'budget_id', 'account_id',
        'jan', 'feb', 'mar', 'apr', 'may', 'jun',
        'jul', 'aug', 'sep', 'oct', 'nov', 'dec',
    ];

    protected function casts(): array
    {
        return [
            'jan' => 'decimal:2', 'feb' => 'decimal:2', 'mar' => 'decimal:2',
            'apr' => 'decimal:2', 'may' => 'decimal:2', 'jun' => 'decimal:2',
            'jul' => 'decimal:2', 'aug' => 'decimal:2', 'sep' => 'decimal:2',
            'oct' => 'decimal:2', 'nov' => 'decimal:2', 'dec' => 'decimal:2',
        ];
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function getAnnualTotal(): float
    {
        return $this->jan + $this->feb + $this->mar + $this->apr
             + $this->may + $this->jun + $this->jul + $this->aug
             + $this->sep + $this->oct + $this->nov + $this->dec;
    }

    public function getMonthAmount(int $month): float
    {
        $months = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
        return $this->{$months[$month - 1]} ?? 0;
    }
}
