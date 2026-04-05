<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class EmployeeDocument extends Model
{
    public const TYPE_KTP     = 'ktp';
    public const TYPE_NPWP    = 'npwp';
    public const TYPE_IJAZAH  = 'ijazah';

    public const STATUS_PENDING  = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';

    public const ALLOWED_TYPES = [self::TYPE_KTP, self::TYPE_NPWP, self::TYPE_IJAZAH];

    public const TYPE_LABELS = [
        self::TYPE_KTP    => 'KTP (ID Card)',
        self::TYPE_NPWP   => 'NPWP (Tax ID)',
        self::TYPE_IJAZAH => 'Ijazah (Diploma)',
    ];

    protected $fillable = [
        'employee_id', 'type', 'file_path', 'file_hash',
        'original_name', 'mime_type', 'file_size',
        'status', 'rejection_reason',
        'verified_by', 'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'file_size'   => 'integer',
        ];
    }

    // ── Relationships ─────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ── Helpers ───────────────────────────────────────────────

    public function getTemporaryUrl(): string
    {
        return URL::temporarySignedRoute(
            'profile.ess.document.view',
            now()->addMinutes(5),
            ['document' => $this->id]
        );
    }

    public function fileExists(): bool
    {
        return Storage::disk('local')->exists($this->file_path);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    public static function typeOptions(): array
    {
        return self::ALLOWED_TYPES;
    }
}
