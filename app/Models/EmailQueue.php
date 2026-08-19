<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailQueue extends Model
{
    use HasFactory;

    protected $table = 'email_queue';

    protected $fillable = [
        'vendor_id', 'campaign_id', 'generated_email_id',
        'recipient_email', 'subject', 'body', 'status',
        'scheduled_at', 'scheduled_date', 'sent_at', 'attempts', 'max_attempts',
        'last_error', 'smtp_response', 'message_id',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'scheduled_date' => 'datetime',
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
}
