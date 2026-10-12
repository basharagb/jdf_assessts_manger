<?php

namespace Database\Factories;

use App\Models\MarketGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MarketGroup> */
class MarketGroupFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->city(), 'slug' => fake()->unique()->slug(2), 'accent' => '#2563eb'];
    }
}
