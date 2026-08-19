<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id', 'campaign_id', 'original_email_id', 'sequence',
        'delay_days', 'scheduled_date', 'subject', 'body', 'status',
        'auto_send', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'sent_at' => 'datetime',
            'auto_send' => 'boolean',
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

    public function originalEmail(): BelongsTo
    {
        return $this->belongsTo(GeneratedEmail::class, 'original_email_id');
    }
}
