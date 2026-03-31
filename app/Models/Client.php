<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'email', 'phone', 'company', 'tax_id',
        'address', 'city', 'country', 'type', 'status', 'notes',
    ];

    // Relationships
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('company', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%");
        });
    }

    // Auto-generate code on creation
    protected static function booted(): void
    {
        static::creating(function (Client $client) {
            if (empty($client->code)) {
                $last = static::withTrashed()->max('id') ?? 0;
                $client->code = 'CLT-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
