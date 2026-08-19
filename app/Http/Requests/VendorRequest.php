<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand_name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'secondary_email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'product_category' => 'nullable|string|max:255',
            'amazon_brand_store' => 'nullable|string|max:255',
            'contact_source' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high,critical',
            'status' => 'nullable|in:new,researching,ready_to_contact,contacted,replied,interested,not_interested,approved,rejected,follow_up_required,opted_out,invalid_email,archived',
            'email_status' => 'nullable|in:not_sent,draft,ready,scheduled,sending,sent,failed,cancelled,opted_out,replied',
            'next_follow_up' => 'nullable|date',
        ];
    }
}
