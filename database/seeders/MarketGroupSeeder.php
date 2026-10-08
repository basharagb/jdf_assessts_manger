<?php

namespace Database\Seeders;

use App\Models\MarketGroup;
use Illuminate\Database\Seeder;

class MarketGroupSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'أسواق الشمال', 'slug' => 'north', 'accent' => '#2d7ff9'],
            ['name' => 'أسواق الوسط', 'slug' => 'center', 'accent' => '#16a67a'],
            ['name' => 'أسواق الجنوب', 'slug' => 'south', 'accent' => '#d99535'],
        ] as $group) {
            MarketGroup::updateOrCreate(['slug' => $group['slug']], $group);
        }
    }
}
