<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_date',
        'channel',
        'ticket_code',
        'queue_seq',
        'queue_number',
        'full_name',
        'email',
        'phone',
        'institution',
        'purpose',
        'pst_channel',
        'service_type',
        'non_pst_type',
        'detail',
        'complaint_title',
        'photo_path',
        'consent_at',
        'status',
        'assigned_officer_id',
        'called_at',
        'started_at',
        'finished_at',
        'officer_note',
        'skm_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date:Y-m-d',
            'consent_at' => 'datetime',
            'called_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'skm_sent_at' => 'datetime',
            'queue_seq' => 'integer',
        ];
    }

    public function setVisitDateAttribute($value): void
    {
        $this->attributes['visit_date'] = $value instanceof \DateTimeInterface 
            ? $value->format('Y-m-d') 
            : substr((string) $value, 0, 10);
    }

    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_officer_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(VisitAttachment::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(VisitStatusLog::class);
    }

    public function callEvents(): HasMany
    {
        return $this->hasMany(CallEvent::class);
    }
}
