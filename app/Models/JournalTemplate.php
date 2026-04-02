<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalTemplate extends Model
{
    protected $fillable = ['name', 'description', 'items', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return [
            'items'     => 'array',
            'is_active' => 'boolean',
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
}
