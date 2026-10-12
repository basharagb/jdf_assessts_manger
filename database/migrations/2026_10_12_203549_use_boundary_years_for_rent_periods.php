<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rent periods now store their end as the anniversary year on which they finish,
 * so "2022 → 2023" is one rental year and the next period can start in 2023.
 * Single-year periods saved as "2022 → 2022" are moved to "2022 → 2023";
 * longer ranges were entered as boundaries already and keep their years.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('market_rent_periods')->whereColumn('end_year', 'start_year')->update(['end_year' => DB::raw('start_year + 1')]);
    }

    /**
     * Rolling back cannot tell converted single-year periods from boundary ranges,
     * so every one-year boundary period returns to the old "same year" form.
     */
    public function down(): void
    {
        DB::table('market_rent_periods')->whereRaw('end_year = start_year + 1')->update(['end_year' => DB::raw('start_year')]);
    }
};
