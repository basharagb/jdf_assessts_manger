<?php

namespace Database\Factories;

use App\Models\Market;
use App\Models\MarketGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Market> */
class MarketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'market_group_id' => MarketGroup::factory(), 'name' => fake()->company(),
            'area' => 100, 'lessor' => fake()->company(), 'annual_rent' => 5000,
            'lease_start_date' => now()->subYearsNoOverflow(2)->toDateString(),
            'lease_end_date' => now()->addYearNoOverflow()->toDateString(),
            'is_active' => true, 'is_demo' => false,
        ];
    }
}
