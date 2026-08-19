<?php

namespace App\Services;

use App\Models\Vendor;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\VendorImport;

class CsvImportService
{
    public function parseCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['success' => false, 'error' => 'Cannot open file'];
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return ['success' => false, 'error' => 'Empty or invalid CSV file'];
        }

        $headers = array_map(fn($h) => trim(strtolower($h)), $headers);

        $rows = [];
        $rowCount = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count(array_filter($row)) === 0) {
                continue;
            }
            $rows[] = $row;
            $rowCount++;
            if ($rowCount >= 5000) {
                break;
            }
        }
        fclose($handle);

        return [
            'success' => true,
            'headers' => $headers,
            'rows' => $rows,
            'total' => count($rows),
        ];
    }

    public function detectColumnMapping(array $headers): array
    {
        $mapping = [];
        $fieldMap = [
            'brand_name' => ['brand_name', 'brand', 'brand name'],
            'company_name' => ['company_name', 'company', 'company name', 'business name'],
            'contact_name' => ['contact_name', 'contact', 'name', 'contact person', 'person'],
            'contact_email' => ['contact_email', 'email', 'email_address', 'email address', 'e-mail'],
            'secondary_email' => ['secondary_email', 'secondary email', 'alt email', 'alternative email'],
            'phone' => ['phone', 'phone_number', 'phone number', 'telephone', 'tel'],
            'website' => ['website', 'website_url', 'url', 'site', 'web'],
            'product_category' => ['product_category', 'category', 'product category', 'product type'],
            'country' => ['country', 'nation'],
            'state' => ['state', 'province', 'region'],
            'city' => ['city', 'town'],
            'amazon_brand_store' => ['amazon_brand_store', 'amazon store', 'amazon', 'amazon url'],
            'contact_source' => ['contact_source', 'source', 'contact source'],
            'notes' => ['notes', 'note', 'comments', 'comment', 'description'],
        ];

        foreach ($fieldMap as $field => $aliases) {
            foreach ($headers as $index => $header) {
                if (in_array($header, $aliases)) {
                    $mapping[$field] = $index;
                    break;
                }
            }
        }

        return $mapping;
    }

    public function validateAndImport(array $headers, array $rows, array $columnMapping, ?int $userId = null): array
    {
        $imported = 0;
        $duplicates = 0;
        $invalidEmails = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $data = [];
            foreach ($columnMapping as $field => $colIndex) {
                if (isset($row[$colIndex])) {
                    $data[$field] = trim($row[$colIndex]);
                }
            }

            if (empty($data['brand_name'])) {
                $skipped++;
                $errors[] = "Row " . ($index + 2) . ": Missing brand name";
                continue;
            }

            if (!empty($data['contact_email'])) {
                if (!filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
                    $invalidEmails++;
                    $data['email_status'] = 'not_sent';
                    $data['status'] = 'invalid_email';
                } else {
                    $existing = Vendor::where('contact_email', $data['contact_email'])
                        ->where('brand_name', $data['brand_name'])
                        ->first();

                    if ($existing) {
                        $duplicates++;
                        continue;
                    }
                }
            }

            if ($userId) {
                $data['user_id'] = $userId;
            }

            try {
                Vendor::create($data);
                $imported++;
            } catch (\Exception $e) {
                $skipped++;
                $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        return [
            'imported' => $imported,
            'duplicates' => $duplicates,
            'invalid_emails' => $invalidEmails,
            'skipped' => $skipped,
            'total' => count($rows),
            'errors' => $errors,
        ];
    }
}
