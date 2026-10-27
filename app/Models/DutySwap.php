<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DutySwap extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'duty_date',
        'original_officer_id',
        'replacement_officer_id',
        'reason',
        'swapped_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'duty_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function originalOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'original_officer_id');
    }

    public function replacementOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replacement_officer_id');
    }

    public function swappedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'swapped_by');
    }
}
