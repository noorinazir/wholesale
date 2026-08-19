<?php

namespace Database\Factories;

use App\Models\Vendor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'brand_name' => fake()->company(),
            'company_name' => fake()->company() . ' Inc',
            'website' => fake()->url(),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'country' => fake()->country(),
            'state' => fake()->state(),
            'city' => fake()->city(),
            'product_category' => fake()->randomElement(['Pet Supplies', 'Home Goods', 'Electronics', 'Beauty', 'Sports', 'Toys']),
            'contact_source' => fake()->randomElement(['Amazon', 'Google Search', 'Trade Show', 'Referral']),
            'notes' => fake()->optional(0.3)->sentence(),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'status' => 'new',
            'email_status' => 'not_sent',
        ];
    }
}
