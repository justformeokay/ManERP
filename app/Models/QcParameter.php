<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QcParameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'type', 'unit', 'min_value', 'max_value', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_value' => 'decimal:4',
            'max_value' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    // Helpers
    public static function typeOptions(): array
    {
        return ['numeric', 'boolean', 'text'];
    }
}
