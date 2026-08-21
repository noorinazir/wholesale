<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\GeneratedEmail;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AI\EmailPersonalizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateBasedEmailGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_based_generation_uses_user_template_and_no_ai(): void
    {
        $user = User::factory()->create();
        $user->assignRole('administrator');

        $company = Company::create([
            'company_name' => 'Test Company LLC',
            'contact_person' => 'John Doe',
            'contact_email' => 'john@testcompany.com',
            'website' => 'https://testcompany.com',
            'phone' => '555-1234',
            'resell_tax_id' => 'TAX-123',
            'is_active' => true,
        ]);

        $vendor = Vendor::create([
            'brand_name' => 'Acme Brands',
            'contact_name' => 'Jane Smith',
            'contact_email' => 'jane@acme.com',
            'product_category' => 'Electronics',
            'status' => 'new',
        ]);

        $template = EmailTemplate::create([
            'user_id' => $user->id,
            'name' => 'Wholesale Template',
            'type' => 'wholesale_inquiry',
            'subject_template' => 'Wholesale Inquiry - {{brand_name}}',
            'body_template' => "Hi {{contact_name}},\n\nWe're {{company_name}} and we'd like to partner with {{brand_name}} for {{category}}.\n\n{{signature}}",
            'is_active' => true,
            'is_default' => true,
        ]);

        $service = app(EmailPersonalizationService::class);

        $result = $service->generateEmail(
            $vendor,
            $company,
            $user,
            'Wholesale Authorization',
            'professional',
            null,
            null,
            false
        );

        $this->assertTrue($result['success']);
        $this->assertInstanceOf(GeneratedEmail::class, $result['email']);
        $this->assertStringContainsString('Acme Brands', $result['email']->subject);
        $this->assertStringContainsString('Jane', $result['email']->body);
        $this->assertStringContainsString('Test Company LLC', $result['email']->body);
        $this->assertStringContainsString('Electronics', $result['email']->body);
        $this->assertNull($result['email']->ai_model);
        $this->assertEquals($template->id, $result['email']->email_template_id);
        $this->assertNull($result['ai_generation_id']);
    }

    public function test_template_based_generation_falls_back_to_builtin_when_no_user_template(): void
    {
        $user = User::factory()->create();

        $vendor = Vendor::create([
            'brand_name' => 'Test Brand',
            'contact_name' => 'Bob Wilson',
            'contact_email' => 'bob@testbrand.com',
            'product_category' => 'Home Goods',
            'status' => 'new',
        ]);

        $service = app(EmailPersonalizationService::class);

        $result = $service->generateEmail(
            $vendor,
            null,
            $user,
            'Wholesale Authorization',
            'professional',
            null,
            null,
            false
        );

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Test Brand', $result['email']->subject);
        $this->assertStringContainsString('Bob', $result['email']->body);
        $this->assertNull($result['email']->ai_model);
    }
}
