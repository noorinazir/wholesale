<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'vendor_id', 'campaign_id', 'generated_email_id', 'email_queue_id',
        'user_id', 'recipient', 'subject', 'body', 'campaign_name',
        'generated_by', 'ai_model', 'created_at', 'approved_at',
        'scheduled_at', 'sent_at', 'status', 'smtp_response', 'error',
        'message_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'approved_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
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

    public function generatedEmail(): BelongsTo
    {
        return $this->belongsTo(GeneratedEmail::class);
    }

    public function queueItem(): BelongsTo
    {
        return $this->belongsTo(EmailQueue::class, 'email_queue_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(EmailEvent::class);
    }
}
