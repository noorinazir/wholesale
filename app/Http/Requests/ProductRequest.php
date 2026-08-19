<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asin' => 'nullable|string|max:20',
            'upc' => 'nullable|string|max:20',
            'product_name' => 'required|string|max:255',
            'product_category' => 'nullable|string|max:255',
            'image_url' => 'nullable|string|max:500',
            'buying_price' => 'nullable|numeric|min:0',
            'fba_fee' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'labeling_cost' => 'nullable|numeric|min:0',
            'other_costs' => 'nullable|numeric|min:0',
            'operation_cost' => 'nullable|numeric|min:0',
            'amazon_sell_price' => 'nullable|numeric|min:0',
            'fba_buy_box_price' => 'nullable|numeric|min:0',
            'fbm_buy_box_price' => 'nullable|numeric|min:0',
            'number_of_sellers' => 'nullable|integer|min:0',
            'buy_box_type' => 'nullable|in:fba,fbm,none',
            'bsr_rank' => 'nullable|integer|min:0',
            'review_count' => 'nullable|integer|min:0',
            'review_rating' => 'nullable|numeric|min:0|max:5',
            'referral_fee_percent' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable|in:active,inactive,discontinued',
            'notes' => 'nullable|string',
        ];
    }
}
