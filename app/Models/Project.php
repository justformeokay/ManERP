<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'description', 'client_id', 'manager_id',
        'status', 'start_date', 'end_date', 'budget', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'budget'     => 'decimal:2',
        ];
    }

    // Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // Scopes
    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%")
              ->orWhereHas('client', fn($c) => $c->where('name', 'like', "%{$term}%"));
        });
    }

    // Auto-generate code
    protected static function booted(): void
    {
        static::creating(function (Project $project) {
            if (empty($project->code)) {
                $last = static::withTrashed()->max('id') ?? 0;
                $project->code = 'PRJ-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public static function statusOptions(): array
    {
        return ['draft', 'active', 'on_hold', 'completed', 'cancelled'];
    }

    public static function statusColors(): array
    {
        return [
            'draft'     => 'bg-gray-100 text-gray-700 ring-gray-500/20',
            'active'    => 'bg-primary-50 text-primary-700 ring-primary-600/20',
            'on_hold'   => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            'completed' => 'bg-green-50 text-green-700 ring-green-600/20',
            'cancelled' => 'bg-red-50 text-red-700 ring-red-600/20',
        ];
    }
}
