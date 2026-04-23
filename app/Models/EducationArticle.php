<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationArticle extends Model
{
    protected $fillable = [
        'title', 'slug', 'category', 'content', 'icon', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('content', 'like', "%{$term}%");
        });
    }

    // ── Helpers ──

    public static function categoryOptions(): array
    {
        return ['glossary', 'workflow', 'tutorial'];
    }

    public function getRenderedContentAttribute(): string
    {
        return \Illuminate\Support\Str::markdown($this->content, [
            'html_input'         => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
