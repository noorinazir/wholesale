<?php

namespace App\Support;

class CategoryOptions
{
    public static function categories(): array
    {
        return [
            'Pet Supplies' => 'Pet Supplies',
            'Health & Beauty' => 'Health & Beauty',
            'Home & Kitchen' => 'Home & Kitchen',
            'Electronics' => 'Electronics',
            'Sports & Outdoors' => 'Sports & Outdoors',
            'Toys & Games' => 'Toys & Games',
            'Baby Products' => 'Baby Products',
            'Grocery & Gourmet' => 'Grocery & Gourmet',
            'Office Products' => 'Office Products',
            'Arts & Crafts' => 'Arts & Crafts',
            'Automotive' => 'Automotive',
            'Industrial' => 'Industrial',
            'Clothing & Apparel' => 'Clothing & Apparel',
            'Jewelry' => 'Jewelry',
            'Books' => 'Books',
            'Other' => 'Other',
        ];
    }

    public static function countries(): array
    {
        return [
            'USA' => 'United States',
            'UK' => 'United Kingdom',
            'CA' => 'Canada',
            'DE' => 'Germany',
        ];
    }

    public static function sources(): array
    {
        return [
            'Amazon Directory' => 'Amazon Directory',
            'Amazon Search' => 'Amazon Search',
            'Referral' => 'Referral',
            'Trade Show' => 'Trade Show',
            'LinkedIn' => 'LinkedIn',
            'Google Search' => 'Google Search',
            'Email List' => 'Email List',
            'Other' => 'Other',
        ];
    }
}
