<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected $fillable = [
        'ticket_number', 'user_id', 'title', 'category', 'priority',
        'status', 'description', 'assigned_to', 'resolved_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'closed_at'   => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportTicketReply::class)->orderBy('created_at');
    }

    public static function generateTicketNumber(): string
    {
        $prefix = 'TKT-' . now()->format('Ymd');
        $last = static::where('ticket_number', 'like', $prefix . '%')
            ->orderByDesc('ticket_number')
            ->value('ticket_number');

        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public static function categories(): array
    {
        return ['general', 'bug', 'feature', 'billing', 'other'];
    }

    public static function priorities(): array
    {
        return ['low', 'medium', 'high', 'critical'];
    }

    public static function statuses(): array
    {
        return ['open', 'in_progress', 'resolved', 'closed'];
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'in_progress']);
    }
}
