<?php

namespace App\Services\AI;

use App\Models\Vendor;
use App\Models\Company;

class TemplateEngine
{
    public function buildEmail(
        Vendor $vendor,
        ?Company $company,
        string $objective,
        string $tone,
        array $personalization
    ): array {
        $contactName = $vendor->contact_name
            ? explode(' ', $vendor->contact_name)[0]
            : 'there';

        $brandName = $vendor->brand_name ?? 'your brand';
        $category = $vendor->product_category ?? 'your products';
        $companyName = $company?->company_name ?? 'our company';
        $website = $company?->website ?? '';
        $contactPerson = $company?->contact_person ?? '';
        $contactEmail = $company?->contact_email ?? '';
        $phone = $company?->phone ?? '';
        $taxId = $company?->resell_tax_id ?? '';
        $amazonStore = $company?->amazon_store_url ?? '';

        $opening = $personalization['opening'] ?? '';
        $categoryQuestion = $personalization['category_question'] ?? '';
        $valueProp = $personalization['value_prop'] ?? '';

        if (empty($valueProp)) {
            $valueProp = '';
        } else {
            $valueProp = rtrim($valueProp) . ' ';
        }

        if (empty($categoryQuestion)) {
            $categoryQuestion = 'Do you have a wholesale or dealer program available?';
        }

        $signature = "Best regards,\n";
        if ($contactPerson) $signature .= "{$contactPerson}\n";
        $signature .= "{$companyName}\n";
        if ($website) $signature .= "{$website}\n";
        if ($contactEmail) $signature .= "{$contactEmail}\n";
        if ($phone) $signature .= "{$phone}\n";

        $templates = $this->getTemplates();
        $template = $templates[$objective] ?? $templates['Wholesale Authorization'];

        $subject = strtr($template['subject'], [
            '{{brand_name}}' => $brandName,
        ]);

        $body = strtr($template['body'], [
            '{{contact_name}}' => $contactName,
            '{{brand_name}}' => $brandName,
            '{{category}}' => $category,
            '{{company_name}}' => $companyName,
            '{{opening}}' => $opening,
            '{{category_question}}' => $categoryQuestion,
            '{{value_prop}}' => $valueProp,
            '{{signature}}' => trim($signature),
            '{{tax_id}}' => $taxId,
            '{{amazon_store}}' => $amazonStore,
        ]);

        $body = preg_replace('/\n{3,}/', "\n\n", $body);
        $body = preg_replace('/^\s+\n/m', "", $body);

        return [
            'subject' => $subject,
            'body' => $body,
            'personalization_notes' => $personalization['notes'] ?? '',
        ];
    }

    public function buildFollowUp(
        Vendor $vendor,
        ?Company $company,
        string $originalSubject,
        int $sequence
    ): array {
        $contactName = $vendor->contact_name
            ? explode(' ', $vendor->contact_name)[0]
            : 'there';
        $brandName = $vendor->brand_name ?? 'your brand';
        $companyName = $company?->company_name ?? 'our company';
        $contactPerson = $company?->contact_person ?? '';
        $website = $company?->website ?? '';
        $contactEmail = $company?->contact_email ?? '';
        $phone = $company?->phone ?? '';

        $signature = "Best regards,\n";
        if ($contactPerson) $signature .= "{$contactPerson}\n";
        $signature .= "{$companyName}\n";
        if ($website) $signature .= "{$website}\n";
        if ($contactEmail) $signature .= "{$contactEmail}\n";
        if ($phone) $signature .= "{$phone}\n";

        if ($sequence === 1) {
            $subject = "Re: {$originalSubject}";
            $body = "Hi {$contactName},\n\nI wanted to follow up on my previous email regarding a potential wholesale partnership with {$brandName}. We're still very interested in exploring opportunities to distribute your products on Amazon.\n\nWould you be available for a quick call this week to discuss?\n\n" . trim($signature);
        } elseif ($sequence === 2) {
            $subject = "Re: {$originalSubject}";
            $body = "Hi {$contactName},\n\nJust checking in — I know things can get busy. We'd love to move forward with a wholesale partnership for {$brandName}. Happy to provide any documentation you need (resale certificate, EIN, etc.).\n\nLet me know if there's anything I can answer.\n\n" . trim($signature);
        } else {
            $subject = "Re: {$originalSubject}";
            $body = "Hi {$contactName},\n\nI understand if the timing isn't right. If you'd like to revisit a wholesale partnership with {$brandName} in the future, I'm happy to pick up the conversation anytime.\n\n" . trim($signature);
        }

        return [
            'subject' => $subject,
            'body' => $body,
            'personalization_notes' => "Template-based follow-up #{$sequence}",
        ];
    }

    public function buildDocumentResponse(
        Vendor $vendor,
        ?Company $company,
        string $replySubject,
        string $replyBody,
        array $personalization
    ): array {
        $contactName = $vendor->contact_name
            ? explode(' ', $vendor->contact_name)[0]
            : 'there';
        $brandName = $vendor->brand_name ?? 'your brand';
        $companyName = $company?->company_name ?? 'our company';
        $contactPerson = $company?->contact_person ?? '';
        $website = $company?->website ?? '';
        $contactEmail = $company?->contact_email ?? '';
        $phone = $company?->phone ?? '';
        $taxId = $company?->resell_tax_id ?? '';
        $ein = $company?->ein ?? '';

        $signature = "Best regards,\n";
        if ($contactPerson) $signature .= "{$contactPerson}\n";
        $signature .= "{$companyName}\n";
        if ($website) $signature .= "{$website}\n";
        if ($contactEmail) $signature .= "{$contactEmail}\n";
        if ($phone) $signature .= "{$phone}\n";

        $opening = $personalization['opening'] ?? "Thank you for your reply and interest in a wholesale partnership.";

        $attachedDocs = $personalization['attached_documents'] ?? [];
        $requestedDocs = $personalization['requested_documents'] ?? [];

        $attachmentLine = '';
        if (!empty($attachedDocs)) {
            $attachmentLine = "I've attached the following document(s) to this email: " . implode(', ', $attachedDocs) . ".";
        } else {
            $attachmentLine = "I'll prepare and send the requested document(s) shortly. Please let me know your preferred delivery method.";
        }

        $idLine = '';
        if ($taxId) $idLine .= "Our Resale Tax ID is {$taxId}. ";
        if ($ein) $idLine .= "Our EIN is {$ein}.";

        $body = "Hi {$contactName},\n\n{$opening}\n\n{$attachmentLine}";
        if ($idLine) $body .= "\n\n{$idLine}";
        $body .= "\n\nCould you let me know the next steps in your vendor approval process? We're ready to move forward at your convenience.\n\n" . trim($signature);

        return [
            'subject' => "Re: {$replySubject}",
            'body' => $body,
            'personalization_notes' => $personalization['notes'] ?? 'Template-based document response',
            'requested_documents' => $requestedDocs,
            'attached_documents' => $attachedDocs,
        ];
    }

    private function getTemplates(): array
    {
        return [
            'Wholesale Authorization' => [
                'subject' => 'Wholesale Partnership Inquiry — {{brand_name}}',
                'body' => "Hi {{contact_name}},\n\n{{opening}}\n\nWe're {{company_name}}, and we're interested in establishing a wholesale partnership to distribute {{brand_name}} products on Amazon. {{value_prop}}\n\nWe have an established Amazon storefront and hold a valid resale certificate. We believe {{brand_name}} would be a strong fit for our catalog.\n\n{{category_question}}\n\nIf you're open to it, I'd love to share our resale tax ID and business details to get the approval process started.\n\n{{signature}}",
            ],
            'Product Inquiry' => [
                'subject' => 'Product Distribution Inquiry — {{brand_name}}',
                'body' => "Hi {{contact_name}},\n\n{{opening}}\n\nWe're {{company_name}} and we specialize in distributing quality brands on Amazon. We came across {{brand_name}} and see strong potential for {{category}} in our marketplace.\n\n{{value_prop}}\n\n{{category_question}}\n\nWe'd appreciate information on your wholesale program, pricing tiers, and minimum order requirements.\n\n{{signature}}",
            ],
            'Brand Authorization' => [
                'subject' => 'Amazon Brand Authorization Request — {{brand_name}}',
                'body' => "Hi {{contact_name}},\n\n{{opening}}\n\nWe're {{company_name}}, an Amazon seller looking to become an authorized distributor for {{brand_name}}. We have the infrastructure and sales channels to represent your brand effectively on Amazon.\n\n{{value_prop}}\n\n{{category_question}}\n\nWe can provide our resale certificate, EIN, and business license to support the authorization process.\n\n{{signature}}",
            ],
            'Partnership Request' => [
                'subject' => 'Partnership Opportunity — {{brand_name}} on Amazon',
                'body' => "Hi {{contact_name}},\n\n{{opening}}\n\nWe're {{company_name}} and we help brands like {{brand_name}} expand their reach on Amazon through authorized wholesale distribution.\n\n{{value_prop}}\n\n{{category_question}}\n\nWe'd love to discuss how we can work together. Happy to share our business credentials and answer any questions.\n\n{{signature}}",
            ],
        ];
    }
}
