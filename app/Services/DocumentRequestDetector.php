<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyDocument;

class DocumentRequestDetector
{
    private array $patterns = [
        'resell_tax_id' => [
            'resale tax id',
            'resale certificate',
            'reseller permit',
            'sales tax id',
            'tax exempt certificate',
            'resale number',
            'reseller certificate',
            'tax id',
            'seller\'s permit',
        ],
        'ein' => [
            'ein',
            'employer identification number',
            'federal tax id',
            'federal employer id',
            'fein',
        ],
        'business_license' => [
            'business license',
            'business registration',
            'trade license',
            'operating license',
            'company registration',
            'certificate of incorporation',
            'articles of incorporation',
        ],
        'w9' => [
            'w-9',
            'w9 form',
            'w 9',
            'tax form w9',
        ],
    ];

    public function detectRequestedDocuments(string $text): array
    {
        $textLower = strtolower($text);
        $requested = [];

        foreach ($this->patterns as $docType => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($textLower, $keyword)) {
                    $requested[] = $docType;
                    break;
                }
            }
        }

        return array_unique($requested);
    }

    public function getAvailableDocuments(Company $company): \Illuminate\Support\Collection
    {
        return $company->documents()->orderBy('type')->get();
    }

    public function getMatchingDocuments(Company $company, array $requestedTypes): \Illuminate\Support\Collection
    {
        if (empty($requestedTypes)) {
            return collect();
        }

        return $company->documents()->whereIn('type', $requestedTypes)->orderBy('type')->get();
    }

    public function buildDocumentContext(Company $company, array $requestedTypes): string
    {
        $matching = $this->getMatchingDocuments($company, $requestedTypes);

        if ($matching->isEmpty()) {
            $labels = [
                'resell_tax_id' => 'Resale Tax ID',
                'ein' => 'EIN document',
                'business_license' => 'Business License',
                'w9' => 'W-9 form',
            ];
            $missing = array_map(fn($t) => $labels[$t] ?? ucfirst(str_replace('_', ' ', $t)), $requestedTypes);
            return "The vendor has requested the following documents: " . implode(', ', $missing) .
                ". However, these documents are NOT currently uploaded in the system. " .
                "In the email, mention that you will provide the requested documents and ask for their preferred method of receiving them (email attachment, portal upload, etc.).";
        }

        $docLabels = [
            'resell_tax_id' => 'Resale Tax ID certificate',
            'ein' => 'EIN verification letter',
            'business_license' => 'Business License',
            'w9' => 'W-9 form',
            'other' => 'supporting document',
        ];

        $available = [];
        foreach ($matching as $doc) {
            $label = $docLabels[$doc->type] ?? ucfirst(str_replace('_', ' ', $doc->type));
            $available[] = "{$label} (file: {$doc->original_name})";
        }

        return "The vendor has requested documents. The following are available and should be attached to the email: " .
            implode(', ', $available) .
            ". In the email body, mention that you have attached the requested document(s) and provide any relevant identification numbers (Resale Tax ID, EIN, etc.) from the company profile.";
    }
}
