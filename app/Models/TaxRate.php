<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_code', 'state_name', 'sales_tax_rate', 'additional_rate',
        'combined_rate', 'has_marketplace_facilitator', 'effective_date', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'sales_tax_rate' => 'decimal:2',
            'additional_rate' => 'decimal:2',
            'combined_rate' => 'decimal:2',
            'has_marketplace_facilitator' => 'boolean',
            'effective_date' => 'date',
        ];
    }

    public static function seedUsStates(): array
    {
        return [
            ['AL', 'Alabama', 4.00, 5.14, 9.14, true],
            ['AK', 'Alaska', 0.00, 1.76, 1.76, false],
            ['AZ', 'Arizona', 5.60, 2.80, 8.40, true],
            ['AR', 'Arkansas', 6.50, 2.99, 9.49, true],
            ['CA', 'California', 7.25, 1.31, 8.56, true],
            ['CO', 'Colorado', 2.90, 4.87, 7.77, true],
            ['CT', 'Connecticut', 6.35, 0.00, 6.35, true],
            ['DE', 'Delaware', 0.00, 0.00, 0.00, false],
            ['FL', 'Florida', 6.00, 1.05, 7.05, true],
            ['GA', 'Georgia', 4.00, 3.29, 7.29, true],
            ['HI', 'Hawaii', 4.00, 0.44, 4.44, true],
            ['ID', 'Idaho', 6.00, 0.02, 6.02, true],
            ['IL', 'Illinois', 6.25, 2.49, 8.74, true],
            ['IN', 'Indiana', 7.00, 0.00, 7.00, true],
            ['IA', 'Iowa', 6.00, 0.94, 6.94, true],
            ['KS', 'Kansas', 6.50, 2.18, 8.68, true],
            ['KY', 'Kentucky', 6.00, 0.00, 6.00, true],
            ['LA', 'Louisiana', 4.45, 5.00, 9.45, true],
            ['ME', 'Maine', 5.50, 0.00, 5.50, true],
            ['MD', 'Maryland', 6.00, 0.00, 6.00, true],
            ['MA', 'Massachusetts', 6.25, 0.00, 6.25, true],
            ['MI', 'Michigan', 6.00, 0.00, 6.00, true],
            ['MN', 'Minnesota', 6.88, 0.58, 7.46, true],
            ['MS', 'Mississippi', 7.00, 0.07, 7.07, true],
            ['MO', 'Missouri', 4.23, 3.90, 8.13, true],
            ['MT', 'Montana', 0.00, 0.00, 0.00, false],
            ['NE', 'Nebraska', 5.50, 1.43, 6.93, true],
            ['NV', 'Nevada', 6.85, 1.29, 8.14, true],
            ['NH', 'New Hampshire', 0.00, 0.00, 0.00, false],
            ['NJ', 'New Jersey', 6.63, 0.00, 6.63, true],
            ['NM', 'New Mexico', 5.13, 2.71, 7.84, true],
            ['NY', 'New York', 4.00, 4.49, 8.49, true],
            ['NC', 'North Carolina', 4.75, 2.22, 6.97, true],
            ['ND', 'North Dakota', 5.00, 1.96, 6.96, true],
            ['OH', 'Ohio', 5.75, 1.48, 7.23, true],
            ['OK', 'Oklahoma', 4.50, 4.45, 8.95, true],
            ['OR', 'Oregon', 0.00, 0.00, 0.00, false],
            ['PA', 'Pennsylvania', 6.00, 0.34, 6.34, true],
            ['RI', 'Rhode Island', 7.00, 0.00, 7.00, true],
            ['SC', 'South Carolina', 6.00, 1.42, 7.42, true],
            ['SD', 'South Dakota', 4.50, 1.90, 6.40, true],
            ['TN', 'Tennessee', 7.00, 2.47, 9.47, true],
            ['TX', 'Texas', 6.25, 1.94, 8.19, true],
            ['UT', 'Utah', 5.95, 1.23, 7.18, true],
            ['VT', 'Vermont', 6.00, 0.18, 6.18, true],
            ['VA', 'Virginia', 4.30, 1.67, 5.97, true],
            ['WA', 'Washington', 6.50, 2.67, 9.17, true],
            ['WV', 'West Virginia', 6.00, 0.39, 6.39, true],
            ['WI', 'Wisconsin', 5.00, 0.44, 5.44, true],
            ['WY', 'Wyoming', 4.00, 1.36, 5.36, true],
            ['DC', 'District of Columbia', 6.00, 0.00, 6.00, true],
        ];
    }
}
