<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Splits the markets that the official JDF site lists with separate halls into sections.
 * Area, base rent and any recorded rent periods are shared equally between the new
 * sections, so the market totals stay the same until the real values are entered.
 */
return new class extends Migration
{
    /** @var array<string, list<string>> */
    private const SECTIONS = [
        'سوق جابر (القادمون والمغادرون)' => ['القادمون', 'المغادرون'],
        'سوق جسر الملك حسين (القادمون والمغادرون)' => ['القادمون', 'المغادرون'],
        'سوق المعبر الجنوبي (القادمون والمغادرون)' => ['القادمون', 'المغادرون'],
        'سوق العمري (القادمون والمغادرون والشحن)' => ['القادمون', 'المغادرون', 'الشحن'],
        'سوق المدورة (المسافرون والشحن)' => ['المسافرون', 'الشحن'],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();
            foreach (DB::table('markets')->whereIn('name', array_keys(self::SECTIONS))->get() as $market) {
                if (DB::table('market_sections')->where('market_id', $market->id)->exists()) {
                    continue;
                }
                $names = self::SECTIONS[$market->name];
                $areas = $this->shares($market->area, count($names));
                $rents = $this->shares($market->annual_rent, count($names));
                $sectionIds = [];
                foreach ($names as $index => $name) {
                    $sectionIds[] = DB::table('market_sections')->insertGetId([
                        'market_id' => $market->id, 'name' => $name, 'area' => $areas[$index],
                        'annual_rent' => $rents[$index], 'sort_order' => $index, 'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
                foreach (DB::table('market_rent_periods')->where('market_id', $market->id)->whereNull('market_section_id')->get() as $period) {
                    $amounts = $this->shares($period->annual_rent ?? 0, count($sectionIds));
                    foreach ($sectionIds as $index => $sectionId) {
                        DB::table('market_rent_periods')->insert([
                            'market_id' => $market->id, 'market_section_id' => $sectionId,
                            'start_year' => $period->start_year, 'end_year' => $period->end_year,
                            'annual_rent' => $amounts[$index], 'status' => $period->status,
                            'notes' => $period->notes, 'created_at' => $period->created_at, 'updated_at' => $now,
                        ]);
                    }
                    DB::table('market_rent_periods')->where('id', $period->id)->delete();
                }
            }
        });
    }

    /**
     * Merges section periods that share the same years and status back into one market period.
     */
    public function down(): void
    {
        DB::transaction(function (): void {
            $now = now();
            foreach (DB::table('markets')->whereIn('name', array_keys(self::SECTIONS))->get() as $market) {
                $periods = DB::table('market_rent_periods')->where('market_id', $market->id)->whereNotNull('market_section_id')->get();
                foreach ($periods->groupBy(fn (object $period): string => $period->start_year.'|'.$period->end_year.'|'.$period->status) as $group) {
                    $first = $group->first();
                    DB::table('market_rent_periods')->insert([
                        'market_id' => $market->id, 'market_section_id' => null,
                        'start_year' => $first->start_year, 'end_year' => $first->end_year,
                        'annual_rent' => $group->sum(fn (object $period): float => (float) $period->annual_rent),
                        'status' => $first->status, 'notes' => $first->notes,
                        'created_at' => $first->created_at, 'updated_at' => $now,
                    ]);
                }
                DB::table('market_sections')->where('market_id', $market->id)->delete();
            }
        });
    }

    /** @return list<float> */
    private function shares(string|float|int $amount, int $parts): array
    {
        $cents = (int) round((float) $amount * 100);
        $share = intdiv($cents, $parts);
        $amounts = array_fill(0, $parts, $share / 100);
        $amounts[$parts - 1] = ($cents - $share * ($parts - 1)) / 100;

        return $amounts;
    }
};
