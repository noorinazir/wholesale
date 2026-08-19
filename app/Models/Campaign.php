<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'description', 'objective', 'status',
        'started_at', 'completed_at',
        'auto_approve', 'auto_followup_enabled', 'followup_delay_days', 'max_followups',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'auto_approve' => 'boolean',
            'auto_followup_enabled' => 'boolean',
            'followup_delay_days' => 'integer',
            'max_followups' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class, 'campaign_vendors')
            ->withPivot('status', 'email_generated_at', 'approved_at', 'sent_at')
            ->withTimestamps();
    }

    public function generatedEmails(): HasMany
    {
        return $this->hasMany(GeneratedEmail::class);
    }

    public function emailQueueItems(): HasMany
    {
        return $this->hasMany(EmailQueue::class);
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }
}
