<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->catchPhrase() . ' Campaign',
            'description' => fake()->optional(0.5)->sentence(),
            'objective' => fake()->randomElement([
                'Wholesale Authorization',
                'Reseller Authorization',
                'Amazon Authorization',
                'Distributor Pricing',
                'Product Catalog Request',
            ]),
            'status' => 'draft',
        ];
    }
}
