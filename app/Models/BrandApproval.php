<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id', 'approval_status', 'submitted_at', 'approved_at', 'expires_at',
        'approved_categories', 'minimum_order_qty', 'payment_terms', 'lead_time_days',
        'exclusive_territories', 'pricing_tier', 'discount_percent', 'contact_person',
        'approval_document_url',
        'requires_exclusivity', 'requires_map_policy', 'requires_brand_registry',
        'requirements_notes',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'date',
            'approved_at' => 'date',
            'expires_at' => 'date',
            'approved_categories' => 'array',
            'exclusive_territories' => 'array',
            'minimum_order_qty' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'requires_exclusivity' => 'boolean',
            'requires_map_policy' => 'boolean',
            'requires_brand_registry' => 'boolean',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->approval_status) {
            'approved' => 'green',
            'rejected' => 'red',
            'under_review' => 'blue',
            'submitted' => 'indigo',
            'expired' => 'gray',
            default => 'yellow',
        };
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->approval_status === 'approved'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
