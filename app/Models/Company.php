<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'company_name', 'legal_company_name', 'resell_tax_id', 'ein',
        'website',
        'business_description', 'business_address', 'country', 'state_province',
        'city', 'contact_person', 'contact_email', 'phone', 'amazon_store_url',
        'amazon_marketplace', 'years_in_business', 'business_model',
        'product_categories', 'brands_represented', 'sales_channels',
        'estimated_annual_purchasing_volume', 'estimated_monthly_purchasing_volume',
        'target_brands', 'additional_information', 'custom_notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'years_in_business' => 'integer',
            'estimated_annual_purchasing_volume' => 'decimal:2',
            'estimated_monthly_purchasing_volume' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CompanyDocument::class);
    }

    public function getDocumentByType(string $type): ?CompanyDocument
    {
        return $this->documents()->where('type', $type)->latest()->first();
    }
}
