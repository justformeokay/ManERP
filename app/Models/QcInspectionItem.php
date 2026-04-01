<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QcInspectionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'qc_inspection_id', 'qc_parameter_id', 'min_value', 'max_value',
        'measured_value', 'result', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'min_value' => 'decimal:4',
            'max_value' => 'decimal:4',
        ];
    }

    // Relationships
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(QcInspection::class, 'qc_inspection_id');
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(QcParameter::class, 'qc_parameter_id');
    }

    // Helpers
    public static function resultOptions(): array
    {
        return ['pending', 'pass', 'fail'];
    }
}
