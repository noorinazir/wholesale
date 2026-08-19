<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'brand_name', 'company_name', 'website', 'contact_name',
        'contact_email', 'secondary_email', 'phone', 'country', 'state', 'city',
        'product_category', 'amazon_brand_store', 'vendor_website', 'contact_source',
        'notes', 'priority', 'status', 'email_status', 'last_contacted_at',
        'next_follow_up', 'research_summary', 'research_data', 'researched_at',
    ];

    protected function casts(): array
    {
        return [
            'last_contacted_at' => 'datetime',
            'next_follow_up' => 'date',
            'researched_at' => 'datetime',
            'research_data' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(VendorContact::class);
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_vendors')
            ->withPivot('status', 'email_generated_at', 'approved_at', 'sent_at')
            ->withTimestamps();
    }

    public function generatedEmails(): HasMany
    {
        return $this->hasMany(GeneratedEmail::class);
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    public function emailQueueItems(): HasMany
    {
        return $this->hasMany(EmailQueue::class);
    }

    public function emailReplies(): HasMany
    {
        return $this->hasMany(EmailReply::class)->latest('received_at');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->latest();
    }

    public function amazonOrders(): HasMany
    {
        return $this->hasMany(AmazonOrder::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function brandApproval(): HasOne
    {
        return $this->hasOne(BrandApproval::class)->latest();
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    public function suppressionListEntries(): HasMany
    {
        return $this->hasMany(SuppressionList::class);
    }

    public function isOptedOut(): bool
    {
        return $this->status === 'opted_out' || $this->email_status === 'opted_out';
    }

    public function hasValidEmail(): bool
    {
        return filter_var($this->contact_email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['archived', 'opted_out', 'invalid_email']);
    }

    public function scopeNotContacted(Builder $query): Builder
    {
        return $query->whereNull('last_contacted_at');
    }

    public function scopeFollowUpDue(Builder $query): Builder
    {
        return $query->where('next_follow_up', '<=', now()->toDateString())
            ->whereNotIn('status', ['opted_out', 'archived']);
    }
}
