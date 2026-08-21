<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SystemSetting;

class KimiService
{
    private string $apiKey;
    private string $model;
    private float $temperature;
    private int $maxTokens;
    private string $baseUrl;

    public function __construct()
    {
        $envApiKey = (string) (config('services.kimi.api_key', '') ?? '');

        try {
            if (!empty($envApiKey)) {
                $this->apiKey = $envApiKey;
            } else {
                $this->apiKey = (string) (SystemSetting::get('kimi_api_key', '') ?? '');
            }
            $this->model = (string) (SystemSetting::get('kimi_model', config('services.kimi.model', 'kimi-k3')) ?? 'kimi-k3');
            $this->temperature = (float) (SystemSetting::get('kimi_temperature', config('services.kimi.temperature', 0.7)) ?? 0.7);
            $this->maxTokens = (int) (SystemSetting::get('kimi_max_tokens', config('services.kimi.max_tokens', 800)) ?? 800);
        } catch (\Throwable $e) {
            Log::warning('KimiService constructor failed to load settings: ' . $e->getMessage());
            $this->apiKey = $envApiKey;
            $this->model = 'kimi-k3';
            $this->temperature = 0.7;
            $this->maxTokens = 800;
        }
        $this->baseUrl = (string) config('services.kimi.base_url', 'https://api.moonshot.ai/v1');
    }

    public function chat(array $messages, ?array $options = []): array
    {
        $startTime = microtime(true);

        $payload = array_merge([
            'model' => $options['model'] ?? $this->model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? $this->temperature,
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
        ], $options);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(120)
                ->withOptions(['verify' => config('app.env') === 'local' ? false : true])
                ->post($this->baseUrl . '/chat/completions', $payload);

            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                Log::error('Kimi API error', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);

                return [
                    'success' => false,
                    'error' => 'API returned status ' . $response->status(),
                    'content' => null,
                    'usage' => null,
                    'response_time_ms' => $responseTimeMs,
                ];
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;
            $usage = $data['usage'] ?? null;

            return [
                'success' => true,
                'content' => $content,
                'usage' => $usage,
                'model' => $payload['model'],
                'response_time_ms' => $responseTimeMs,
                'error' => null,
            ];
        } catch (\Exception $e) {
            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);
            Log::error('Kimi API exception', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'content' => null,
                'usage' => null,
                'response_time_ms' => $responseTimeMs,
            ];
        }
    }

    public function testConnection(): array
    {
        $result = $this->chat([
            ['role' => 'user', 'content' => 'Hello, please respond with "Connection successful".'],
        ], ['max_tokens' => 20]);

        return [
            'success' => $result['success'],
            'message' => $result['success'] ? 'Connection successful' : ($result['error'] ?? 'Connection failed'),
            'model' => $this->model,
        ];
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    public function getModel(): string
    {
        return $this->model;
    }
}
