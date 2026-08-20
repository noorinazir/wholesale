<?php

namespace App\Services\AI;

use App\Models\Company;
use App\Models\Vendor;
use App\Models\GeneratedEmail;
use App\Models\AiGeneration;
use Illuminate\Support\Facades\Log;

class PromptBuilder
{
    public function buildEmailGenerationPrompt(
        Vendor $vendor,
        ?Company $company,
        string $objective = 'Wholesale Authorization',
        string $tone = 'professional',
        ?string $customInstructions = null,
        ?string $previousHistory = null
    ): array {
        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt = $this->buildUserPrompt($vendor, $company, $objective, $tone, $customInstructions, $previousHistory);

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];
    }

    public function buildModificationPrompt(GeneratedEmail $email, string $instruction): array
    {
        $systemPrompt = "You are an expert B2B wholesale business development assistant. Your task is to modify the provided email according to the user's instructions. Return the modified email in the same JSON format.";

        $userPrompt = "Please modify the following email:\n\n";
        $userPrompt .= "Subject: {$email->subject}\n\n";
        $userPrompt .= "Body:\n{$email->body}\n\n";
        $userPrompt .= "Modification instruction: {$instruction}\n\n";
        $userPrompt .= "Return JSON:\n{\"subject\": \"...\", \"body\": \"...\", \"personalization_notes\": \"...\"}";

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];
    }

    public function buildPersonalizationPrompt(
        Vendor $vendor,
        ?Company $company,
        string $objective,
        string $tone,
        ?string $customInstructions = null
    ): array {
        $systemPrompt = "You are a B2B outreach assistant. Generate 3 short personalized snippets for a wholesale email. Be concise, factual, and professional. Do not invent facts.";

        $userPrompt = "VENDOR:\n";
        $userPrompt .= "Brand: {$vendor->brand_name}\n";
        if ($vendor->product_category) $userPrompt .= "Category: {$vendor->product_category}\n";
        if ($vendor->website) $userPrompt .= "Website: {$vendor->website}\n";
        if ($vendor->contact_name) $userPrompt .= "Contact: {$vendor->contact_name}\n";
        if ($vendor->notes) $userPrompt .= "Notes: {$vendor->notes}\n";

        $userPrompt .= "\nOUR COMPANY: " . ($company?->company_name ?? 'N/A') . "\n";
        if ($company?->business_description) $userPrompt .= "About: " . mb_substr($company->business_description, 0, 200) . "\n";
        if ($company?->amazon_store_url) $userPrompt .= "Amazon Store: {$company->amazon_store_url}\n";

        $userPrompt .= "\nOBJECTIVE: {$objective}\n";
        $userPrompt .= "TONE: {$tone}\n";

        if ($customInstructions) {
            $userPrompt .= "INSTRUCTIONS: {$customInstructions}\n";
        }

        $userPrompt .= "\nGenerate:\n";
        $userPrompt .= "1. opening: 1-2 sentences mentioning the brand and why we're interested (max 40 words)\n";
        $userPrompt .= "2. value_prop: 1 sentence on why our company is a good reseller (max 25 words)\n";
        $userPrompt .= "3. category_question: 1 relevant question about their wholesale/dealer program (max 20 words)\n";
        $userPrompt .= "\nReturn JSON: {\"opening\": \"...\", \"value_prop\": \"...\", \"category_question\": \"...\", \"notes\": \"...\"}";

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];
    }

    public function buildResearchPrompt(Vendor $vendor): array
    {
        $systemPrompt = "You are a B2B research assistant. Analyze the provided vendor information and provide useful insights for wholesale outreach.";

        $userPrompt = "Analyze this vendor/brand for wholesale outreach:\n\n";
        $userPrompt .= "Brand Name: {$vendor->brand_name}\n";
        $userPrompt .= "Company: {$vendor->company_name}\n";
        $userPrompt .= "Website: {$vendor->website}\n";
        $userPrompt .= "Category: {$vendor->product_category}\n";
        $userPrompt .= "Amazon Store: {$vendor->amazon_brand_store}\n\n";
        $userPrompt .= "Provide:\n";
        $userPrompt .= "1. Likely product categories and positioning\n";
        $userPrompt .= "2. Potential wholesale/dealer program\n";
        $userPrompt .= "3. Suggested outreach approach\n";
        $userPrompt .= "4. Questions to ask\n\n";
        $userPrompt .= "Clearly distinguish between verified information and AI inference. Return as JSON:\n";
        $userPrompt .= "{\"summary\": \"...\", \"positioning\": \"...\", \"wholesale_info\": \"...\", \"suggested_approach\": \"...\", \"questions\": [\"...\"], \"verified_info\": \"...\", \"ai_inference\": \"...\"}";

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];
    }

    public function buildFollowUpPrompt(
        Vendor $vendor,
        ?Company $company,
        string $originalSubject,
        string $originalBody,
        int $sequence
    ): array {
        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt = "Write a follow-up email (follow-up #{$sequence}) for the following vendor.\n\n";
        $userPrompt .= "VENDOR INFORMATION:\n{$this->formatVendorInfo($vendor)}\n\n";
        $userPrompt .= "COMPANY INFORMATION:\n{$this->formatCompanyInfo($company)}\n\n";
        $userPrompt .= "ORIGINAL EMAIL SUBJECT: {$originalSubject}\n\n";
        $userPrompt .= "ORIGINAL EMAIL BODY:\n{$originalBody}\n\n";
        $userPrompt .= "EMAIL OBJECTIVE: Follow-up on previous wholesale inquiry\n\n";
        $userPrompt .= "Requirements:\n";
        $userPrompt .= "1. Reference the previous email politely\n";
        $userPrompt .= "2. Keep it very short — 2 to 3 brief sentences plus signature. Do not repeat content from the original email.\n";
        $userPrompt .= "3. Reiterate interest without being pushy\n";
        $userPrompt .= "4. Offer to provide additional information\n";
        $userPrompt .= "5. Use the actual Contact Person, Contact Email, Phone, Company name, and Website from the COMPANY INFORMATION for the signature. Never use placeholders.\n";
        $userPrompt .= "6. Return JSON: {\"subject\": \"...\", \"body\": \"...\", \"personalization_notes\": \"...\"}";

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];
    }

    private function buildSystemPrompt(): string
    {
        return "You are an expert B2B wholesale business development assistant.\n\n" .
            "Your task is to write a concise, professional, personalized wholesale inquiry email.\n\n" .
            "Requirements:\n" .
            "1. Address the contact by name when available.\n" .
            "2. Mention the brand naturally.\n" .
            "3. Demonstrate genuine interest in the brand/product category.\n" .
            "4. Explain why our company may be a suitable reseller.\n" .
            "5. Clearly state the wholesale/reseller request.\n" .
            "6. Ask relevant next-step questions.\n" .
            "7. Keep the email professional, concise, and short — maximum 3 to 4 brief paragraphs. Do not repeat information or over-explain. Get to the point quickly.\n" .
            "8. Do not invent facts.\n" .
            "9. Do not claim authorization that does not exist.\n" .
            "10. Do not fabricate sales figures.\n" .
            "11. Do not make unsupported claims about the vendor.\n" .
            "12. Avoid excessive marketing language.\n" .
            "13. Avoid spam-like wording.\n" .
            "14. Avoid unnecessary emojis.\n" .
            "15. Do not use deceptive subject lines.\n" .
            "16. Return a clean subject and email body.\n" .
            "17. Use the actual Contact Person, Contact Email, Phone, Company name, and Website from the COMPANY INFORMATION for the email signature. Never use placeholder text like [Your Full Name], [Your Position], or [Your Contact Information]. If any contact detail is missing, omit it from the signature rather than using a placeholder.\n\n" .
            "Return JSON:\n" .
            "{\"subject\": \"...\", \"body\": \"...\", \"personalization_notes\": \"...\"}";
    }

    private function buildUserPrompt(
        Vendor $vendor,
        ?Company $company,
        string $objective,
        string $tone,
        ?string $customInstructions,
        ?string $previousHistory
    ): string {
        $prompt = "COMPANY INFORMATION:\n{$this->formatCompanyInfo($company)}\n\n";
        $prompt .= "VENDOR INFORMATION:\n{$this->formatVendorInfo($vendor)}\n\n";
        $prompt .= "EMAIL OBJECTIVE: {$objective}\n\n";
        $prompt .= "TONE: {$tone}\n\n";

        if ($previousHistory) {
            $prompt .= "PREVIOUS COMMUNICATION:\n{$previousHistory}\n\n";
        } else {
            $prompt .= "PREVIOUS COMMUNICATION: None (first contact)\n\n";
        }

        if ($customInstructions) {
            $prompt .= "USER INSTRUCTIONS:\n{$customInstructions}\n\n";
        }

        $prompt .= "Return JSON:\n";
        $prompt .= "{\"subject\": \"...\", \"body\": \"...\", \"personalization_notes\": \"...\"}";

        return $prompt;
    }

    private function formatCompanyInfo(?Company $company): string
    {
        if (!$company) {
            return "No company profile available. Use generic professional B2B language.";
        }

        $parts = [];
        if ($company->company_name) $parts[] = "Company: {$company->company_name}";
        if ($company->legal_company_name) $parts[] = "Legal Name: {$company->legal_company_name}";
        if ($company->resell_tax_id) $parts[] = "Resale Tax ID: {$company->resell_tax_id}";
        if ($company->ein) $parts[] = "EIN: {$company->ein}";
        if ($company->website) $parts[] = "Website: {$company->website}";
        if ($company->business_description) $parts[] = "Description: {$company->business_description}";
        if ($company->amazon_store_url) $parts[] = "Amazon Store: {$company->amazon_store_url}";
        if ($company->amazon_marketplace) $parts[] = "Amazon Marketplace: {$company->amazon_marketplace}";
        if ($company->years_in_business) $parts[] = "Years in Business: {$company->years_in_business}";
        if ($company->business_model) $parts[] = "Business Model: {$company->business_model}";
        if ($company->product_categories) $parts[] = "Product Categories: {$company->product_categories}";
        if ($company->brands_represented) $parts[] = "Brands Represented: {$company->brands_represented}";
        if ($company->sales_channels) $parts[] = "Sales Channels: {$company->sales_channels}";
        if ($company->estimated_annual_purchasing_volume) $parts[] = "Annual Purchasing Volume: \${$company->estimated_annual_purchasing_volume}";
        if ($company->estimated_monthly_purchasing_volume) $parts[] = "Monthly Purchasing Volume: \${$company->estimated_monthly_purchasing_volume}";
        if ($company->target_brands) $parts[] = "Target Brands: {$company->target_brands}";
        if ($company->additional_information) $parts[] = "Additional Info: {$company->additional_information}";
        if ($company->contact_person) $parts[] = "Contact Person: {$company->contact_person}";
        if ($company->contact_email) $parts[] = "Contact Email: {$company->contact_email}";
        if ($company->phone) $parts[] = "Phone: {$company->phone}";

        $availableDocs = $company->documents()->pluck('type')->unique()->toArray();
        if (!empty($availableDocs)) {
            $docLabels = [
                'resell_tax_id' => 'Resale Tax ID certificate',
                'ein' => 'EIN verification letter',
                'business_license' => 'Business License',
                'other' => 'Other supporting documents',
            ];
            $docList = array_map(fn($t) => $docLabels[$t] ?? ucfirst(str_replace('_', ' ', $t)), $availableDocs);
            $parts[] = "Available Documents for Attachment: " . implode(', ', $docList);
        }

        return implode("\n", $parts);
    }

    public function formatCompanyInfoPublic(?Company $company): string
    {
        return $this->formatCompanyInfo($company);
    }

    public function formatVendorInfoPublic(Vendor $vendor): string
    {
        return $this->formatVendorInfo($vendor);
    }

    private function formatVendorInfo(Vendor $vendor): string
    {
        $parts = [];
        if ($vendor->brand_name) $parts[] = "Brand: {$vendor->brand_name}";
        if ($vendor->company_name) $parts[] = "Company: {$vendor->company_name}";
        if ($vendor->contact_name) $parts[] = "Contact: {$vendor->contact_name}";
        if ($vendor->contact_email) $parts[] = "Email: {$vendor->contact_email}";
        if ($vendor->website) $parts[] = "Website: {$vendor->website}";
        if ($vendor->product_category) $parts[] = "Category: {$vendor->product_category}";
        if ($vendor->amazon_brand_store) $parts[] = "Amazon Brand Store: {$vendor->amazon_brand_store}";
        if ($vendor->country) $parts[] = "Country: {$vendor->country}";
        if ($vendor->notes) $parts[] = "Notes: {$vendor->notes}";
        if ($vendor->research_summary) $parts[] = "Research: {$vendor->research_summary}";

        return implode("\n", $parts);
    }
}
