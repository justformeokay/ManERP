<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'email', 'phone', 'company', 'tax_id',
        'npwp', 'tax_address', 'is_pkp',
        'address', 'city', 'country', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_pkp' => 'boolean',
        ];
    }

    // Relationships
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
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

    // Auto-generate code
    protected static function booted(): void
    {
        static::creating(function (Supplier $supplier) {
            if (empty($supplier->code)) {
                $last = static::withTrashed()->max('id') ?? 0;
                $supplier->code = 'SUP-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
