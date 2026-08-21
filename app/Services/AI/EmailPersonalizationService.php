<?php

namespace App\Services\AI;

use App\Models\AiGeneration;
use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\GeneratedEmail;
use App\Models\User;
use App\Models\Vendor;
use App\Services\DocumentRequestDetector;
use Illuminate\Support\Facades\Log;

class EmailPersonalizationService
{
    public function __construct(
        private KimiService $kimiService,
        private PromptBuilder $promptBuilder,
        private DocumentRequestDetector $documentDetector,
        private TemplateEngine $templateEngine
    ) {}

    public function generateEmail(
        Vendor $vendor,
        ?Company $company,
        ?User $user = null,
        string $objective = 'Wholesale Authorization',
        string $tone = 'professional',
        ?string $customInstructions = null,
        ?int $campaignId = null,
        bool $useAI = false
    ): array {
        if (!$useAI) {
            return $this->generateFromTemplate($vendor, $company, $user, $objective, $tone, $customInstructions, $campaignId);
        }

        $personalization = $this->getAIPersonalization($vendor, $company, $objective, $tone, $customInstructions, $user);

        $emailData = $this->templateEngine->buildEmail($vendor, $company, $objective, $tone, $personalization);

        $qualityChecks = $this->runQualityChecks($emailData, $vendor);

        $generatedEmail = GeneratedEmail::create([
            'vendor_id' => $vendor->id,
            'campaign_id' => $campaignId,
            'user_id' => $user?->id,
            'subject' => $emailData['subject'],
            'body' => $emailData['body'],
            'personalization_notes' => $emailData['personalization_notes'],
            'tone' => $tone,
            'objective' => $objective,
            'custom_instructions' => $customInstructions,
            'ai_model' => $this->kimiService->getModel(),
            'status' => 'draft',
            'quality_checks' => $qualityChecks,
        ]);

        return [
            'success' => true,
            'email' => $generatedEmail,
            'quality_checks' => $qualityChecks,
            'ai_generation_id' => $personalization['ai_generation_id'] ?? null,
        ];
    }

    public function generateFromTemplate(
        Vendor $vendor,
        ?Company $company,
        ?User $user = null,
        string $objective = 'Wholesale Authorization',
        string $tone = 'professional',
        ?string $customInstructions = null,
        ?int $campaignId = null
    ): array {
        $userTemplate = $this->templateEngine->findUserTemplate($objective);

        if ($userTemplate) {
            $emailData = $this->templateEngine->buildFromUserTemplate($userTemplate, $vendor, $company);
        } else {
            $personalization = [
                'opening' => "I hope this email finds you well. I'm reaching out because we've been following {$vendor->brand_name} and are impressed with your product line.",
                'value_prop' => '',
                'category_question' => 'Do you have a wholesale or dealer program available?',
                'notes' => 'Default template (no user template configured)',
            ];
            $emailData = $this->templateEngine->buildEmail($vendor, $company, $objective, $tone, $personalization);
        }

        $qualityChecks = $this->runQualityChecks($emailData, $vendor);

        $generatedEmail = GeneratedEmail::create([
            'vendor_id' => $vendor->id,
            'campaign_id' => $campaignId,
            'user_id' => $user?->id,
            'email_template_id' => $emailData['template_id'] ?? null,
            'subject' => $emailData['subject'],
            'body' => $emailData['body'],
            'personalization_notes' => $emailData['personalization_notes'],
            'tone' => $tone,
            'objective' => $objective,
            'custom_instructions' => $customInstructions,
            'ai_model' => null,
            'status' => 'draft',
            'quality_checks' => $qualityChecks,
        ]);

        return [
            'success' => true,
            'email' => $generatedEmail,
            'quality_checks' => $qualityChecks,
            'ai_generation_id' => null,
        ];
    }

    private function getAIPersonalization(
        Vendor $vendor,
        ?Company $company,
        string $objective,
        string $tone,
        ?string $customInstructions,
        ?User $user
    ): array {
        $messages = $this->promptBuilder->buildPersonalizationPrompt($vendor, $company, $objective, $tone, $customInstructions);

        $result = $this->kimiService->chat($messages, ['max_tokens' => 400]);

        $aiGeneration = $this->logGeneration($vendor, $user, $result, $messages, 'personalize_email');

        if (!$result['success']) {
            return [
                'opening' => "I hope this email finds you well. I'm reaching out because we've been following {$vendor->brand_name} and are impressed with your product line.",
                'value_prop' => '',
                'category_question' => 'Do you have a wholesale or dealer program available?',
                'notes' => 'AI personalization failed, used default snippets',
                'ai_generation_id' => $aiGeneration->id ?? null,
            ];
        }

        $parsed = $this->parseResponse($result['content'], 'personalization');

        if (!$parsed['success']) {
            return [
                'opening' => "I hope this email finds you well. I'm reaching out because we've been following {$vendor->brand_name} and are impressed with your product line.",
                'value_prop' => '',
                'category_question' => 'Do you have a wholesale or dealer program available?',
                'notes' => 'AI response parse failed, used default snippets',
                'ai_generation_id' => $aiGeneration->id ?? null,
            ];
        }

        $data = $parsed['data'];
        return [
            'opening' => $data['opening'] ?? '',
            'value_prop' => $data['value_prop'] ?? '',
            'category_question' => $data['category_question'] ?? '',
            'notes' => $data['notes'] ?? '',
            'ai_generation_id' => $aiGeneration->id ?? null,
        ];
    }

    public function modifyEmail(GeneratedEmail $email, string $instruction, ?User $user = null): array
    {
        $messages = $this->promptBuilder->buildModificationPrompt($email, $instruction);
        $result = $this->kimiService->chat($messages);

        $aiGeneration = $this->logGeneration($email->vendor, $user, $result, $messages, 'modify_email');

        if (!$result['success']) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'AI modification failed',
            ];
        }

        $parsed = $this->parseResponse($result['content']);

        if (!$parsed['success']) {
            return [
                'success' => false,
                'error' => $parsed['error'],
            ];
        }

        $email->update([
            'subject' => $parsed['data']['subject'],
            'body' => $parsed['data']['body'],
            'personalization_notes' => $parsed['data']['personalization_notes'] ?? $email->personalization_notes,
            'generation_attempt' => $email->generation_attempt + 1,
        ]);

        return [
            'success' => true,
            'email' => $email->fresh(),
        ];
    }

    public function researchVendor(Vendor $vendor, ?User $user = null): array
    {
        $messages = $this->promptBuilder->buildResearchPrompt($vendor);
        $result = $this->kimiService->chat($messages);

        $this->logGeneration($vendor, $user, $result, $messages, 'research_vendor');

        if (!$result['success']) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Research failed',
            ];
        }

        $parsed = $this->parseResponse($result['content']);

        if (!$parsed['success']) {
            return [
                'success' => false,
                'error' => $parsed['error'],
            ];
        }

        $vendor->update([
            'research_summary' => $parsed['data']['summary'] ?? null,
            'research_data' => $parsed['data'],
            'researched_at' => now(),
            'status' => $vendor->status === 'new' ? 'researching' : $vendor->status,
        ]);

        return [
            'success' => true,
            'data' => $parsed['data'],
        ];
    }

    public function generateTemplate(
        string $type,
        string $tone = 'professional',
        ?string $customInstructions = null,
        ?User $user = null
    ): array {
        if (!$this->kimiService->isConfigured()) {
            return [
                'success' => false,
                'error' => 'AI is not configured. Please set the KIMI_API_KEY in AI Configuration.',
            ];
        }

        $typeLabels = [
            'wholesale_inquiry' => 'Wholesale Inquiry',
            'amazon_reseller' => 'Amazon Reseller Authorization',
            'distributor_inquiry' => 'Distributor Inquiry',
            'catalog_request' => 'Product Catalog Request',
            'dealer_application' => 'Dealer Application',
            'pricing_request' => 'Pricing Request',
        ];

        $typeLabel = $typeLabels[$type] ?? 'Wholesale Inquiry';

        $availableVars = '{{contact_name}}, {{brand_name}}, {{category}}, {{company_name}}, {{website}}, {{contact_person}}, {{contact_email}}, {{phone}}, {{tax_id}}, {{ein}}, {{amazon_store}}, {{signature}}, {{vendor_company}}, {{vendor_website}}, {{vendor_country}}';

        $systemPrompt = "You are a B2B email template generator. Create reusable email templates using {{variable}} placeholders. The templates will be used for wholesale/vendor outreach. Use ONLY the available variables listed. Do not invent new variables. Keep the subject line concise and professional. The body should be 3-5 paragraphs, well-structured, and use a {$tone} tone. Return JSON only.";

        $userPrompt = "Create a reusable email template for: {$typeLabel}\n";
        $userPrompt .= "Tone: {$tone}\n";
        if ($customInstructions) {
            $userPrompt .= "Additional instructions: {$customInstructions}\n";
        }
        $userPrompt .= "\nAvailable variables: {$availableVars}\n";
        $userPrompt .= "\nReturn JSON: {\"name\": \"...\", \"subject_template\": \"...\", \"body_template\": \"...\", \"description\": \"...\"}\n";
        $userPrompt .= "The subject_template and body_template MUST use {{variable}} placeholders (e.g. {{contact_name}}, {{brand_name}}). Do NOT use real names or values.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        $result = $this->kimiService->chat($messages, ['max_tokens' => 800, 'temperature' => 0.8]);

        try {
            $this->logGeneration(null, $user, $result, $messages, 'generate_template');
        } catch (\Exception $e) {
            Log::error('Failed to log AI generation', ['error' => $e->getMessage()]);
        }

        if (!$result['success']) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'AI generation failed',
            ];
        }

        $parsed = $this->parseResponse($result['content']);

        if (!$parsed['success']) {
            return [
                'success' => false,
                'error' => $parsed['error'],
            ];
        }

        $data = $parsed['data'];

        if (empty($data['subject_template']) || empty($data['body_template'])) {
            return [
                'success' => false,
                'error' => 'AI response missing subject_template or body_template',
            ];
        }

        return [
            'success' => true,
            'template' => [
                'name' => $data['name'] ?? $typeLabel . ' Template',
                'subject_template' => $data['subject_template'],
                'body_template' => $data['body_template'],
                'description' => $data['description'] ?? null,
            ],
        ];
    }

    public function generateFollowUp(
        Vendor $vendor,
        ?Company $company,
        GeneratedEmail $originalEmail,
        int $sequence,
        ?User $user = null
    ): array {
        if ($sequence >= 2) {
            $templateData = $this->templateEngine->buildFollowUp(
                $vendor, $company, $originalEmail->subject, $sequence
            );
            return [
                'success' => true,
                'subject' => $templateData['subject'],
                'body' => $templateData['body'],
                'personalization_notes' => $templateData['personalization_notes'],
            ];
        }

        $messages = $this->promptBuilder->buildFollowUpPrompt(
            $vendor, $company, $originalEmail->subject, $originalEmail->body, $sequence
        );

        $result = $this->kimiService->chat($messages, ['max_tokens' => 800]);

        $this->logGeneration($vendor, $user, $result, $messages, 'generate_followup');

        if (!$result['success']) {
            $templateData = $this->templateEngine->buildFollowUp(
                $vendor, $company, $originalEmail->subject, $sequence
            );
            return [
                'success' => true,
                'subject' => $templateData['subject'],
                'body' => $templateData['body'],
                'personalization_notes' => $templateData['personalization_notes'] . ' (AI failed, used template)',
            ];
        }

        $parsed = $this->parseResponse($result['content']);

        if (!$parsed['success']) {
            $templateData = $this->templateEngine->buildFollowUp(
                $vendor, $company, $originalEmail->subject, $sequence
            );
            return [
                'success' => true,
                'subject' => $templateData['subject'],
                'body' => $templateData['body'],
                'personalization_notes' => $templateData['personalization_notes'] . ' (AI parse failed, used template)',
            ];
        }

        return [
            'success' => true,
            'subject' => $parsed['data']['subject'],
            'body' => $parsed['data']['body'],
            'personalization_notes' => $parsed['data']['personalization_notes'] ?? null,
        ];
    }

    public function generateDocumentResponseEmail(
        Vendor $vendor,
        ?Company $company,
        string $replySubject,
        string $replyBody,
        ?User $user = null
    ): array {
        $requestedDocs = $this->documentDetector->detectRequestedDocuments($replySubject . ' ' . $replyBody);

        $matchingDocuments = collect();

        if ($company) {
            $matchingDocuments = $this->documentDetector->getMatchingDocuments($company, $requestedDocs);
        }

        $attachedDocNames = $matchingDocuments->pluck('type')->map(function ($type) {
            $labels = [
                'resell_tax_id' => 'Resale Tax ID certificate',
                'ein' => 'EIN verification letter',
                'business_license' => 'Business License',
                'other' => 'Supporting documents',
            ];
            return $labels[$type] ?? ucfirst(str_replace('_', ' ', $type));
        })->toArray();

        $systemPrompt = "You are a B2B outreach assistant. Write a 1-sentence opening for a document response email. Thank the vendor for their reply and acknowledge their document request. Be concise and professional. Max 30 words.";

        $userPrompt = "VENDOR: {$vendor->brand_name}\n";
        $userPrompt .= "REPLY SUBJECT: {$replySubject}\n";
        $userPrompt .= "REQUESTED DOCUMENTS: " . (empty($requestedDocs) ? 'Not specified' : implode(', ', $requestedDocs)) . "\n";
        $userPrompt .= "\nReturn JSON: {\"opening\": \"...\", \"notes\": \"...\"}";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        $result = $this->kimiService->chat($messages, ['max_tokens' => 200]);

        $aiGeneration = null;
        try {
            $aiGeneration = $this->logGeneration($vendor, $user, $result, $messages, 'document_response');
        } catch (\Exception $e) {
            Log::error('Failed to log AI generation', ['error' => $e->getMessage()]);
        }

        $personalization = [
            'requested_documents' => $requestedDocs,
            'attached_documents' => $attachedDocNames,
            'notes' => 'Template-based document response',
        ];

        if ($result['success']) {
            $parsed = $this->parseResponse($result['content'], 'document_response');
            if ($parsed['success']) {
                $personalization['opening'] = $parsed['data']['opening'] ?? '';
                $personalization['notes'] = $parsed['data']['notes'] ?? 'AI-assisted document response';
            }
        }

        $emailData = $this->templateEngine->buildDocumentResponse(
            $vendor, $company, $replySubject, $replyBody, $personalization
        );

        $qualityChecks = $this->runQualityChecks($emailData, $vendor);

        $generatedEmail = GeneratedEmail::create([
            'vendor_id' => $vendor->id,
            'user_id' => $user?->id,
            'subject' => $emailData['subject'],
            'body' => $emailData['body'],
            'personalization_notes' => $emailData['personalization_notes'],
            'tone' => 'professional',
            'objective' => 'Document Response',
            'ai_model' => $result['model'] ?? $this->kimiService->getModel(),
            'status' => 'draft',
            'quality_checks' => $qualityChecks,
        ]);

        if ($aiGeneration) {
            $aiGeneration->update(['generated_email_id' => $generatedEmail->id]);
        }

        return [
            'success' => true,
            'email' => $generatedEmail,
            'requested_documents' => $requestedDocs,
            'attachments' => $matchingDocuments,
            'ai_generation_id' => $aiGeneration->id ?? null,
        ];
    }

    private function parseResponse(?string $content, ?string $expectedFormat = null): array
    {
        if (!$content) {
            return ['success' => false, 'error' => 'Empty response from AI'];
        }

        $json = $this->extractJson($content);

        if (!$json) {
            return ['success' => false, 'error' => 'Could not parse JSON from AI response'];
        }

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'error' => 'Invalid JSON: ' . json_last_error_msg()];
        }

        if ($expectedFormat === 'personalization') {
            if (empty($data['opening'])) {
                return ['success' => false, 'error' => 'Missing opening in personalization response'];
            }
            return ['success' => true, 'data' => $data];
        }

        if ($expectedFormat === 'document_response') {
            if (empty($data['opening'])) {
                return ['success' => false, 'error' => 'Missing opening in document response'];
            }
            return ['success' => true, 'data' => $data];
        }

        if (empty($data['subject']) || empty($data['body'])) {
            return ['success' => false, 'error' => 'Missing subject or body in AI response'];
        }

        return ['success' => true, 'data' => $data];
    }

    private function extractJson(string $content): ?string
    {
        $content = trim($content);

        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $content;
        }

        if (preg_match('/\{(?:[^{}]|(?:\{[^{}]*\}))*\}/s', $content, $matches)) {
            return $matches[0];
        }

        if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function runQualityChecks(array $emailData, Vendor $vendor): array
    {
        $checks = [];
        $warnings = [];

        if (empty($emailData['subject'])) {
            $checks['subject'] = 'fail';
            $warnings[] = 'Subject is empty';
        } else {
            $checks['subject'] = 'pass';
        }

        if (empty($emailData['body'])) {
            $checks['body'] = 'fail';
            $warnings[] = 'Body is empty';
        } else {
            $checks['body'] = 'pass';
        }

        if (strlen($emailData['body'] ?? '') > 5000) {
            $checks['length'] = 'warning';
            $warnings[] = 'Email body is very long (over 5000 characters)';
        } else {
            $checks['length'] = 'pass';
        }

        $contactFirstName = $vendor->contact_name ? explode(' ', $vendor->contact_name)[0] : '';
        if ($contactFirstName && !str_contains(strtolower($emailData['body'] ?? ''), strtolower($contactFirstName))) {
            $checks['personalization'] = 'warning';
            $warnings[] = 'Contact name not found in email body';
        } else {
            $checks['personalization'] = 'pass';
        }

        if ($vendor->brand_name && !str_contains(strtolower($emailData['body'] ?? ''), strtolower($vendor->brand_name))) {
            $checks['brand_mention'] = 'warning';
            $warnings[] = 'Brand name not found in email body';
        } else {
            $checks['brand_mention'] = 'pass';
        }

        if (preg_match_all('/https?:\/\/[^\s]+/', $emailData['body'] ?? '', $urls)) {
            $checks['urls'] = 'pass';
        } else {
            $checks['urls'] = 'pass';
        }

        $checks['warnings'] = $warnings;
        return $checks;
    }

    private function logGeneration(?Vendor $vendor, ?User $user, array $result, array $messages, string $action): AiGeneration
    {
        return AiGeneration::create([
            'user_id' => $user?->id,
            'vendor_id' => $vendor?->id,
            'model' => $result['model'] ?? $this->kimiService->getModel(),
            'action' => $action,
            'prompt' => json_encode($messages),
            'response' => $result['content'] ?? null,
            'input_tokens' => $result['usage']['prompt_tokens'] ?? null,
            'output_tokens' => $result['usage']['completion_tokens'] ?? null,
            'estimated_cost' => $this->estimateCost($result['usage'] ?? null, $result['model'] ?? ''),
            'success' => $result['success'],
            'error' => $result['error'] ?? null,
            'response_time_ms' => $result['response_time_ms'] ?? null,
        ]);
    }

    private function estimateCost(?array $usage, string $model): ?float
    {
        if (!$usage) {
            return null;
        }

        $inputTokens = $usage['prompt_tokens'] ?? 0;
        $outputTokens = $usage['completion_tokens'] ?? 0;

        $inputRate = 0.0000024;
        $outputRate = 0.0000096;

        return ($inputTokens * $inputRate) + ($outputTokens * $outputRate);
    }
}
