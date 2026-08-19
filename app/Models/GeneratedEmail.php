<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id', 'campaign_id', 'user_id', 'email_template_id',
        'subject', 'body', 'personalization_notes', 'tone', 'objective',
        'custom_instructions', 'ai_model', 'status', 'approved_at',
        'rejected_at', 'generation_attempt', 'quality_checks',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'quality_checks' => 'array',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function queueItem(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EmailQueue::class);
    }
}
