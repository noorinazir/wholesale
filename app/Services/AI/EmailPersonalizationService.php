<?php

namespace App\Services\AI;

use App\Models\AiGeneration;
use App\Models\Company;
use App\Models\GeneratedEmail;
use App\Models\User;
use App\Models\Vendor;
use App\Services\DocumentRequestDetector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmailPersonalizationService
{
    public function __construct(
        private KimiService $kimiService,
        private PromptBuilder $promptBuilder,
        private DocumentRequestDetector $documentDetector
    ) {}

    public function generateEmail(
        Vendor $vendor,
        ?Company $company,
        ?User $user = null,
        string $objective = 'Wholesale Authorization',
        string $tone = 'professional',
        ?string $customInstructions = null,
        ?int $campaignId = null
    ): array {
        $previousHistory = $this->getPreviousHistory($vendor);

        $messages = $this->promptBuilder->buildEmailGenerationPrompt(
            $vendor, $company, $objective, $tone, $customInstructions, $previousHistory
        );

        $result = $this->kimiService->chat($messages);

        $aiGeneration = $this->logGeneration($vendor, $user, $result, $messages, 'generate_email');

        if (!$result['success']) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'AI generation failed',
                'ai_generation_id' => $aiGeneration->id ?? null,
            ];
        }

        $parsed = $this->parseResponse($result['content']);

        if (!$parsed['success']) {
            return [
                'success' => false,
                'error' => $parsed['error'],
                'ai_generation_id' => $aiGeneration->id ?? null,
            ];
        }

        $qualityChecks = $this->runQualityChecks($parsed['data'], $vendor);

        $generatedEmail = GeneratedEmail::create([
            'vendor_id' => $vendor->id,
            'campaign_id' => $campaignId,
            'user_id' => $user?->id,
            'subject' => $parsed['data']['subject'],
            'body' => $parsed['data']['body'],
            'personalization_notes' => $parsed['data']['personalization_notes'] ?? null,
            'tone' => $tone,
            'objective' => $objective,
            'custom_instructions' => $customInstructions,
            'ai_model' => $result['model'] ?? $this->kimiService->getModel(),
            'status' => 'draft',
            'quality_checks' => $qualityChecks,
        ]);

        $aiGeneration->update(['generated_email_id' => $generatedEmail->id]);

        return [
            'success' => true,
            'email' => $generatedEmail,
            'quality_checks' => $qualityChecks,
            'ai_generation_id' => $aiGeneration->id,
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

    public function generateFollowUp(
        Vendor $vendor,
        ?Company $company,
        GeneratedEmail $originalEmail,
        int $sequence,
        ?User $user = null
    ): array {
        $messages = $this->promptBuilder->buildFollowUpPrompt(
            $vendor, $company, $originalEmail->subject, $originalEmail->body, $sequence
        );

        $result = $this->kimiService->chat($messages);

        $this->logGeneration($vendor, $user, $result, $messages, 'generate_followup');

        if (!$result['success']) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Follow-up generation failed',
            ];
        }

        $parsed = $this->parseResponse($result['content']);

        if (!$parsed['success']) {
            return [
                'success' => false,
                'error' => $parsed['error'],
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

        $documentContext = '';
        $matchingDocuments = collect();

        if ($company) {
            $documentContext = $this->documentDetector->buildDocumentContext($company, $requestedDocs);
            $matchingDocuments = $this->documentDetector->getMatchingDocuments($company, $requestedDocs);
        }

        $systemPrompt = "You are an expert B2B wholesale business development assistant.\n\n" .
            "A vendor has replied to your outreach email and requested specific documents (such as Resale Tax ID, EIN, Business License, or W-9).\n" .
            "Your task is to draft a professional response email that:\n" .
            "1. Thanks them for their reply and interest.\n" .
            "2. Acknowledges their document request specifically.\n" .
            "3. Mentions that the requested document(s) are attached to this email (if available).\n" .
            "4. Includes relevant identification numbers from the company profile (Resale Tax ID, EIN) if available.\n" .
            "5. Asks about next steps in the wholesale/vendor approval process.\n" .
            "6. Keeps the email professional, concise, and warm.\n" .
            "7. Does not invent facts or claim to have documents that are not available.\n" .
            "8. If a requested document is NOT available, mentions you will provide it shortly and asks for their preferred delivery method.\n\n" .
            "Return JSON:\n" .
            "{\"subject\": \"...\", \"body\": \"...\", \"personalization_notes\": \"...\", \"requested_documents\": [\"...\"], \"attached_documents\": [\"...\"]}";

        $userPrompt = "VENDOR INFORMATION:\n{$this->promptBuilder->formatVendorInfoPublic($vendor)}\n\n";
        $userPrompt .= "COMPANY INFORMATION:\n{$this->promptBuilder->formatCompanyInfoPublic($company)}\n\n";
        $userPrompt .= "VENDOR'S REPLY SUBJECT: {$replySubject}\n\n";
        $userPrompt .= "VENDOR'S REPLY BODY:\n{$replyBody}\n\n";

        if ($documentContext) {
            $userPrompt .= "DOCUMENT CONTEXT:\n{$documentContext}\n\n";
        }

        $userPrompt .= "Return JSON:\n";
        $userPrompt .= "{\"subject\": \"...\", \"body\": \"...\", \"personalization_notes\": \"...\", \"requested_documents\": [\"...\"], \"attached_documents\": [\"...\"]}";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        $result = $this->kimiService->chat($messages);
        $aiGeneration = $this->logGeneration($vendor, $user, $result, $messages, 'document_response');

        if (!$result['success']) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'AI generation failed',
                'ai_generation_id' => $aiGeneration->id ?? null,
            ];
        }

        $parsed = $this->parseResponse($result['content']);

        if (!$parsed['success']) {
            return [
                'success' => false,
                'error' => $parsed['error'],
                'ai_generation_id' => $aiGeneration->id ?? null,
            ];
        }

        $generatedEmail = GeneratedEmail::create([
            'vendor_id' => $vendor->id,
            'user_id' => $user?->id,
            'subject' => $parsed['data']['subject'],
            'body' => $parsed['data']['body'],
            'personalization_notes' => $parsed['data']['personalization_notes'] ?? null,
            'tone' => 'professional',
            'objective' => 'Document Response',
            'ai_model' => $result['model'] ?? $this->kimiService->getModel(),
            'status' => 'draft',
            'quality_checks' => $this->runQualityChecks($parsed['data'], $vendor),
        ]);

        $aiGeneration->update(['generated_email_id' => $generatedEmail->id]);

        return [
            'success' => true,
            'email' => $generatedEmail,
            'requested_documents' => $requestedDocs,
            'attachments' => $matchingDocuments,
            'ai_generation_id' => $aiGeneration->id,
        ];
    }

    private function parseResponse(?string $content): array
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

        if ($vendor->contact_name && !str_contains(strtolower($emailData['body'] ?? ''), strtolower($vendor->contact_name))) {
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

    private function getPreviousHistory(Vendor $vendor): ?string
    {
        $logs = $vendor->emailLogs()->where('status', 'sent')->orderBy('sent_at', 'desc')->limit(5)->get();

        if ($logs->isEmpty()) {
            return null;
        }

        $history = '';
        foreach ($logs as $log) {
            $history .= "Date: {$log->sent_at}\nSubject: {$log->subject}\nStatus: {$log->status}\n\n";
        }

        return $history;
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
