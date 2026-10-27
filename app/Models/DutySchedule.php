<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DutySchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'duty_date',
        'officer_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'duty_date' => 'date:Y-m-d',
        ];
    }

    public function setDutyDateAttribute($value): void
    {
        $this->attributes['duty_date'] = $value instanceof \DateTimeInterface 
            ? $value->format('Y-m-d') 
            : substr((string) $value, 0, 10);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
