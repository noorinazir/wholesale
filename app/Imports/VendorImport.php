<?php

namespace App\Imports;

use App\Models\Vendor;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class VendorImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row): ?Vendor
    {
        $email = $row['contact_email'] ?? $row['email'] ?? null;

        if ($email) {
            $existing = Vendor::where('contact_email', $email)->first();
            if ($existing) {
                return null;
            }
        }

        return new Vendor([
            'brand_name' => $row['brand_name'] ?? $row['brand'] ?? '',
            'company_name' => $row['company_name'] ?? $row['company'] ?? null,
            'contact_name' => $row['contact_name'] ?? $row['contact'] ?? null,
            'contact_email' => $email,
            'phone' => $row['phone'] ?? null,
            'website' => $row['website'] ?? $row['url'] ?? null,
            'product_category' => $row['product_category'] ?? $row['category'] ?? null,
            'country' => $row['country'] ?? null,
            'state' => $row['state'] ?? null,
            'city' => $row['city'] ?? null,
            'notes' => $row['notes'] ?? null,
        ]);
    }

    public function rules(): array
    {
        return [
            'brand_name' => 'required|string',
            'contact_email' => 'nullable|email',
        ];
    }
}
