<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The rent of the expansion for the period's years, so it can rise year by year like the market rent.
     * Null falls back to the amount recorded on the expansions themselves.
     */
    public function up(): void
    {
        Schema::table('market_rent_periods', function (Blueprint $table): void {
            $table->decimal('expansion_rent', 14, 2)->nullable()->after('annual_rent');
        });
    }

    public function down(): void
    {
        Schema::table('market_rent_periods', function (Blueprint $table): void {
            $table->dropColumn('expansion_rent');
        });
    }
};
