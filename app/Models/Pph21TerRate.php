<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pph21TerRate extends Model
{
    protected $fillable = ['category', 'min_salary', 'max_salary', 'rate'];

    protected function casts(): array
    {
        return [
            'min_salary' => 'decimal:2',
            'max_salary' => 'decimal:2',
            'rate'       => 'decimal:4',
        ];
    }

    /**
     * Look up the TER rate for a given category and monthly gross income.
     */
    public static function getRate(string $category, float $monthlyGross): float
    {
        $row = static::where('category', strtoupper($category))
            ->where('min_salary', '<=', $monthlyGross)
            ->where(function ($q) use ($monthlyGross) {
                $q->where('max_salary', '>', $monthlyGross)
                  ->orWhereNull('max_salary');
            })
            ->first();

        return $row ? (float) $row->rate : 0;
    }
}
