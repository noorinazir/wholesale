<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'vendor_id', 'generated_email_id', 'model', 'action',
        'prompt', 'response', 'input_tokens', 'output_tokens',
        'estimated_cost', 'success', 'error', 'response_time_ms',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'estimated_cost' => 'decimal:6',
            'response_time_ms' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function generatedEmail(): BelongsTo
    {
        return $this->belongsTo(GeneratedEmail::class);
    }
}
