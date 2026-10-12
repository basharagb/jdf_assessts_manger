<?php

namespace App;

use App\Models\Market;
use App\Models\MarketRentPeriod;
use Illuminate\Support\Carbon;

class AnnualLease
{
    public static function earliestDate(): Carbon
    {
        return Carbon::today('Asia/Amman')->subYearsNoOverflow(5);
    }

    public static function anniversary(Market $market, int $year): Carbon
    {
        return $market->lease_start_date->copy()->addYearsNoOverflow($year - $market->lease_start_date->year);
    }

    public static function currentYear(Market $market): int
    {
        $today = Carbon::today('Asia/Amman');
        return $today->lt(self::anniversary($market, $today->year)) ? $today->year - 1 : $today->year;
    }

    public static function firstYear(Market $market): int
    {
        $cutoff = self::earliestDate();
        $year = max($market->lease_start_date->year, $cutoff->year);
        return self::anniversary($market, $year)->lt($cutoff) ? $year + 1 : $year;
    }

    public static function lastYear(Market $market): int
    {
        if ($market->lease_end_date === null) {
            return max(Carbon::today('Asia/Amman')->year + 10, $market->lease_start_date->year + 10);
        }
        $year = $market->lease_end_date->year;
        return self::anniversary($market, $year)->gte($market->lease_end_date) ? $year - 1 : $year;
    }

    /** @return list<array{year: int, start: string, end: string, amount: float, status: string, notes: ?string}> */
    public static function ledger(Market $market, int $from, int $to): array
    {
        $periods = $market->relationLoaded('rentPeriods') ? $market->rentPeriods : $market->rentPeriods()->orderBy('start_year')->get();
        $rows = [];
        for ($year = max($from, $market->lease_start_date->year); $year <= min($to, self::lastYear($market)); $year++) {
            $period = $periods->sortByDesc('start_year')->first(fn (MarketRentPeriod $period): bool => $period->start_year <= $year && ($period->end_year === null || $period->end_year >= $year));
            $rows[] = [
                'year' => $year,
                'start' => self::anniversary($market, $year)->toDateString(),
                'end' => self::anniversary($market, $year + 1)->toDateString(),
                'amount' => $period?->status === 'vacant' ? 0.0 : (float) ($period?->annual_rent ?? $market->annual_rent),
                'status' => $period?->status ?? 'untracked',
                'notes' => $period?->notes,
            ];
        }
        return $rows;
    }

    /** @param list<array{amount: float, status: string}> $rows
     * @return array{years: int, total: float, paid: float, unpaid: float, untracked: float, vacant: int}
     */
    public static function totals(array $rows): array
    {
        $totals = ['years' => count($rows), 'total' => 0, 'paid' => 0, 'unpaid' => 0, 'untracked' => 0, 'vacant' => 0];
        foreach ($rows as $row) {
            if ($row['status'] === 'vacant') {
                $totals['vacant']++;
            } else {
                $cents = (int) round($row['amount'] * 100);
                $totals['total'] += $cents;
                $totals[$row['status']] += $cents;
            }
        }
        foreach (['total', 'paid', 'unpaid', 'untracked'] as $key) {
            $totals[$key] /= 100;
        }
        return $totals;
    }
}
