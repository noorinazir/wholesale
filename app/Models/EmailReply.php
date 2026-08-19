<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id', 'email_log_id', 'from_email', 'from_name',
        'subject', 'body_text', 'body_html', 'message_id',
        'in_reply_to', 'received_at', 'is_read',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'is_read' => 'boolean',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function emailLog(): BelongsTo
    {
        return $this->belongsTo(EmailLog::class);
    }
}
