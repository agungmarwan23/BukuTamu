<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KioskDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'token_hash',
        'activated_by',
        'last_seen_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function activatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function activator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }
}
