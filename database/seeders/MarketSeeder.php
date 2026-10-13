<?php

namespace Database\Seeders;

use App\Models\Market;
use App\Models\MarketGroup;
use Illuminate\Database\Seeder;

class MarketSeeder extends Seeder
{
    public function run(): void
    {
        $groups = MarketGroup::query()->pluck('id', 'slug');
        $demoDate = now()->subYears(5)->toDateString();
        $demoLessor = 'الجهة المؤجرة غير منشورة — بيانات ديمو';
        $demoRent = 5000.00;

        $markets = [
            [
                'group' => 'north', 'name' => 'سوق جابر (القادمون والمغادرون)', 'area' => 100,
                'sections' => ['القادمون', 'المغادرون'],
                'image' => 'markets/demo/jaber.jpg',
                'description' => 'موقع حقيقي: يقع سوق جابر شمال عمّان ويخدم المسافرين بين الأردن وسوريا. الموقع الرسمي يذكر سوقي القادمين والمغادرين.',
            ],
            [
                'group' => 'north', 'name' => 'سوق جسر الشيخ حسين', 'area' => 100,
                'image' => 'markets/demo/jdf-generic-shop.jpg',
                'description' => 'موقع حقيقي: جسر الشيخ حسين شمال غرب عمّان ويخدم المسافرين بين الأردن وفلسطين.',
            ],
            [
                'group' => 'center', 'name' => 'سوق البوليفارد — العبدلي', 'area' => 1250,
                'lessor' => $demoLessor, 'image' => 'markets/demo/boulevard.jpg',
                'description' => 'موقع حقيقي في بوليفارد العبدلي بعمان. المساحة ١٢٥٠ م² وثلاثة طوابق بحسب موقع JDF الرسمي.',
            ],
            [
                'group' => 'center', 'name' => 'سوق جسر الملك حسين (القادمون والمغادرون)', 'area' => 100,
                'sections' => ['القادمون', 'المغادرون'],
                'image' => 'markets/demo/king-hussein-bridge.jpg',
                'description' => 'موقع حقيقي غرب عمّان على جسر الملك حسين، ويضم سوقي القادمين والمغادرين لخدمة المسافرين بين الأردن وفلسطين.',
            ],
            [
                'group' => 'center', 'name' => 'أسواق الكرامة', 'area' => 100,
                'image' => 'markets/demo/jdf-generic-shop.jpg',
                'description' => 'موقع حقيقي شرق عمّان على الحدود الأردنية العراقية.',
            ],
            [
                'group' => 'south', 'name' => 'سوق العمري (القادمون والمغادرون والشحن)', 'area' => 100,
                'sections' => ['القادمون', 'المغادرون', 'الشحن'],
                'image' => 'markets/demo/omari.jpg',
                'description' => 'موقع حقيقي جنوب شرق عمّان على الحدود الأردنية السعودية، ويضم القادمين والمغادرين والشحن بحسب موقع JDF.',
            ],
            [
                'group' => 'south', 'name' => 'سوق المدورة (المسافرون والشحن)', 'area' => 100,
                'sections' => ['المسافرون', 'الشحن'],
                'image' => 'markets/demo/jdf-generic-shop.jpg',
                'description' => 'موقع حقيقي جنوب عمّان على الحدود الأردنية السعودية، ويخدم المسافرين والحجاج عبر صالتي المسافرين والشحن.',
            ],
            [
                'group' => 'south', 'name' => 'سوق النافورة — العقبة', 'area' => 100,
                'image' => 'markets/demo/aqaba-fountain.jpg',
                'description' => 'موقع حقيقي في النافورة مول بمدينة العقبة، وتظهره أخبار JDF الرسمية كموقع للعروض وسوق Cash & Carry.',
            ],
            [
                'group' => 'south', 'name' => 'سوق تالا باي', 'area' => 100,
                'image' => 'markets/demo/tala-bay.jpg',
                'description' => 'موقع حقيقي على الشاطئ الجنوبي في العقبة بحسب موقع JDF الرسمي.',
            ],
            [
                'group' => 'south', 'name' => 'سوق أطقم البواخر', 'area' => 100,
                'image' => 'markets/demo/jdf-generic-shop.jpg',
                'description' => 'موقع حقيقي في العقبة لخدمة أطقم السفن التجارية بحسب موقع JDF الرسمي.',
            ],
            [
                'group' => 'south', 'name' => 'سوق الدرة', 'area' => 100,
                'image' => 'markets/demo/jdf-generic-shop.jpg',
                'description' => 'موقع حقيقي في العقبة على الحدود بين الأردن والسعودية بحسب موقع JDF الرسمي.',
            ],
            [
                'group' => 'south', 'name' => 'سوق محطة الركاب البحرية', 'area' => 100,
                'image' => 'markets/demo/aqaba-passenger-station.jpg',
                'description' => 'موقع حقيقي في محطة الركاب البحرية بالعقبة، وقد أعلنت JDF افتتاح سوق في محطة السفن السياحية.',
            ],
            [
                'group' => 'south', 'name' => 'سوق المعبر الجنوبي (القادمون والمغادرون)', 'area' => 100,
                'sections' => ['القادمون', 'المغادرون'],
                'image' => 'markets/demo/jdf-generic-shop.jpg',
                'description' => 'موقع حقيقي في معبر العقبة الجنوبي لخدمة المسافرين بين العقبة وإيلات بحسب موقع JDF الرسمي.',
            ],
            [
                'group' => 'south', 'name' => 'سوق البتراء', 'area' => 100,
                'image' => 'markets/demo/petra.jpg',
                'description' => 'موقع حقيقي: أعلنت JDF إطلاق سوق في مدينة البتراء بالتزامن مع افتتاح فندق كراون بلازا البتراء.',
            ],
        ];

        foreach ($markets as $market) {
            $groupId = $groups[$market['group']] ?? null;
            if (! $groupId) {
                continue;
            }

            $record = Market::firstOrCreate(
                ['market_group_id' => $groupId, 'name' => $market['name']],
                [
                    'area' => $market['area'],
                    'lessor' => $market['lessor'] ?? $demoLessor,
                    'annual_rent' => $demoRent,
                    'lease_start_date' => $demoDate,
                    'lease_end_date' => null,
                    'image_path' => $market['image'],
                    'description' => $market['description'],
                    'is_active' => true,
                    'is_demo' => true,
                ],
            );
            if (isset($market['sections']) && ! $record->sections()->exists()) {
                $parts = count($market['sections']);
                foreach ($market['sections'] as $index => $name) {
                    $isLast = $index === $parts - 1;
                    $record->sections()->create([
                        'name' => $name,
                        'area' => $isLast ? $market['area'] - round($market['area'] / $parts, 2) * ($parts - 1) : round($market['area'] / $parts, 2),
                        'annual_rent' => $isLast ? $demoRent - round($demoRent / $parts, 2) * ($parts - 1) : round($demoRent / $parts, 2),
                        'sort_order' => $index,
                    ]);
                }
            }
        }
    }
}
