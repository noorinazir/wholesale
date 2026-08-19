<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\SystemSetting;

class AmazonSpApiService
{
    private string $lwaEndpoint = 'https://api.amazon.com/auth/o2/token';
    private string $spApiEndpoint;
    private string $awsRegion;

    public function __construct()
    {
        $this->spApiEndpoint = SystemSetting::get('amazon_sp_api_endpoint', 'https://sellingpartnerapi-na.amazon.com');
        $this->awsRegion = SystemSetting::get('amazon_aws_region', 'us-east-1');
    }

    public function isConfigured(): bool
    {
        return !empty(SystemSetting::get('amazon_lwa_client_id'))
            && !empty(SystemSetting::get('amazon_lwa_client_secret'))
            && !empty(SystemSetting::get('amazon_refresh_token'))
            && !empty(SystemSetting::get('amazon_sp_api_access_key'));
    }

    public function getLwaAccessToken(): string
    {
        return Cache::remember('amazon_lwa_token', 3000, function () {
            $clientId = SystemSetting::get('amazon_lwa_client_id');
            $clientSecret = SystemSetting::get('amazon_lwa_client_secret');
            $refreshToken = SystemSetting::get('amazon_refresh_token');

            $response = Http::asForm()->post($this->lwaEndpoint, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]);

            if (!$response->ok()) {
                Log::error('Amazon LWA token error', ['response' => $response->body()]);
                throw new \Exception('Failed to get LWA access token: ' . $response->body());
            }

            return $response->json('access_token');
        });
    }

    public function makeRequest(string $method, string $path, array $query = [], array $body = null): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Amazon SP-API is not configured. Add credentials in Settings > Amazon API.');
        }

        $accessToken = $this->getLwaAccessToken();
        $accessKey = SystemSetting::get('amazon_sp_api_access_key');
        $secretKey = SystemSetting::get('amazon_sp_api_secret_key');
        $host = parse_url($this->spApiEndpoint, PHP_URL_HOST);

        $url = $this->spApiEndpoint . $path;
        $amzDate = now('UTC')->format('Ymd\THis\Z');
        $dateStamp = now('UTC')->format('Ymd');

        $canonicalQuery = $this->buildCanonicalQuery($query);
        $canonicalHeaders = "host:{$host}\nx-amz-access-token:{$accessToken}\nx-amz-date:{$amzDate}\n";
        $signedHeaders = 'host;x-amz-access-token;x-amz-date';
        $payloadHash = hash('sha256', $body ? json_encode($body) : '');

        $canonicalRequest = implode("\n", [
            strtoupper($method),
            $path,
            $canonicalQuery,
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        $credentialScope = "{$dateStamp}/{$this->awsRegion}/execute-api/aws4_request";
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        $signature = $this->calculateSignature($secretKey, $dateStamp, $stringToSign);

        $authorizationHeader = "AWS4-HMAC-SHA256 Credential={$accessKey}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

        $response = Http::withHeaders([
            'x-amz-access-token' => $accessToken,
            'x-amz-date' => $amzDate,
            'Authorization' => $authorizationHeader,
            'Content-Type' => 'application/json',
        ])->{$method}($url . ($canonicalQuery ? '?' . http_build_query($query) : ''), $body);

        // Handle 403 token expiry — flush cache and retry once
        if ($response->status() === 403 && str_contains($response->body(), 'TokenInvalid')) {
            Cache::forget('amazon_lwa_token');
            $accessToken = $this->getLwaAccessToken();
            $canonicalHeaders = "host:{$host}\nx-amz-access-token:{$accessToken}\nx-amz-date:{$amzDate}\n";
            $canonicalRequest = implode("\n", [
                strtoupper($method),
                $path,
                $canonicalQuery,
                $canonicalHeaders,
                $signedHeaders,
                $payloadHash,
            ]);
            $stringToSign = implode("\n", [
                'AWS4-HMAC-SHA256',
                $amzDate,
                $credentialScope,
                hash('sha256', $canonicalRequest),
            ]);
            $signature = $this->calculateSignature($secretKey, $dateStamp, $stringToSign);
            $authorizationHeader = "AWS4-HMAC-SHA256 Credential={$accessKey}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";
            $response = Http::withHeaders([
                'x-amz-access-token' => $accessToken,
                'x-amz-date' => $amzDate,
                'Authorization' => $authorizationHeader,
                'Content-Type' => 'application/json',
            ])->{$method}($url . ($canonicalQuery ? '?' . http_build_query($query) : ''), $body);
        }

        if (!$response->ok()) {
            Log::error('Amazon SP-API request failed', [
                'method' => $method,
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception("SP-API request failed ({$response->status()}): " . $response->body());
        }

        return $response->json();
    }

    private function buildCanonicalQuery(array $query): string
    {
        ksort($query);
        $pairs = [];
        foreach ($query as $key => $value) {
            $pairs[] = rawurlencode($key) . '=' . rawurlencode($value);
        }
        return implode('&', $pairs);
    }

    private function calculateSignature(string $secretKey, string $dateStamp, string $stringToSign): string
    {
        $kDate = hash_hmac('sha256', $dateStamp, "AWS4" . $secretKey, true);
        $kRegion = hash_hmac('sha256', $this->awsRegion, $kDate, true);
        $kService = hash_hmac('sha256', 'execute-api', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        return hash_hmac('sha256', $stringToSign, $kSigning);
    }

    // === Catalog Items API ===

    public function getCatalogItem(string $asin): array
    {
        return $this->makeRequest('get', '/catalog/2022-04-01/items/' . $asin, [
            'marketplaceIds' => SystemSetting::get('amazon_marketplace_id', 'ATVPDKIKX0DER'),
            'includedData' => 'attributes,images,salesRanks',
        ]);
    }

    // === Product Fees API ===

    public function getFeesEstimate(string $asin, float $price): array
    {
        $body = [
            'feesEstimateRequest' => [
                'marketplaceId' => SystemSetting::get('amazon_marketplace_id', 'ATVPDKIKX0DER'),
                'priceToEstimateFees' => [
                    'totalPrice' => [
                        'currencyCode' => 'USD',
                        'amount' => $price,
                    ],
                ],
                'identifier' => uniqid(),
            ],
        ];

        return $this->makeRequest('post', '/products/fees/v0/items/' . $asin . '/feesEstimate', [], $body);
    }

    // === Pricing API ===

    public function getItemPrice(string $asin): array
    {
        return $this->makeRequest('get', '/products/pricing/v0/items/' . $asin . '/offers', [
            'MarketplaceId' => SystemSetting::get('amazon_marketplace_id', 'ATVPDKIKX0DER'),
        ]);
    }

    // === Orders API ===

    public function getOrders(Carbon $from, Carbon $to, int $limit = 100, ?string $nextToken = null): array
    {
        $params = [
            'MarketplaceIds' => SystemSetting::get('amazon_marketplace_id', 'ATVPDKIKX0DER'),
            'CreatedAfter' => $from->format('Y-m-d\TH:i:s\Z'),
            'CreatedBefore' => $to->format('Y-m-d\TH:i:s\Z'),
            'MaxResultsPerPage' => $limit,
        ];
        if ($nextToken) {
            $params['NextToken'] = $nextToken;
        }
        return $this->makeRequest('get', '/orders/v0/orders', $params);
    }

    public function getOrderItems(string $orderId): array
    {
        return $this->makeRequest('get', '/orders/v0/orders/' . $orderId . '/orderItems', [
            'MarketplaceIds' => SystemSetting::get('amazon_marketplace_id', 'ATVPDKIKX0DER'),
        ]);
    }

    // === FBA Inventory API ===

    public function getFbaInventory(): array
    {
        return $this->makeRequest('get', '/fba/inventory/v1/summaries', [
            'marketplaceIds' => SystemSetting::get('amazon_marketplace_id', 'ATVPDKIKX0DER'),
            'granularityType' => 'Marketplace',
            'granularityId' => SystemSetting::get('amazon_marketplace_id', 'ATVPDKIKX0DER'),
        ]);
    }

    // === Reports API (settlement/financial) ===

    public function createReport(string $reportType, Carbon $from, Carbon $to): array
    {
        $body = [
            'reportType' => $reportType,
            'marketplaceIds' => [SystemSetting::get('amazon_marketplace_id', 'ATVPDKIKX0DER')],
            'dataStartTime' => $from->format('Y-m-d\TH:i:s\Z'),
            'dataEndTime' => $to->format('Y-m-d\TH:i:s\Z'),
        ];

        return $this->makeRequest('post', '/reports/2021-06-30/reports', [], $body);
    }

    public function getReport(string $reportId): array
    {
        return $this->makeRequest('get', '/reports/2021-06-30/reports/' . $reportId);
    }

    public function getReportDocument(string $documentId): array
    {
        return $this->makeRequest('get', '/reports/2021-06-30/documents/' . $documentId);
    }
}
