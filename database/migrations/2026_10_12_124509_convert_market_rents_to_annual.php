<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('markets', 'monthly_rent')) {
            Schema::table('markets', fn (Blueprint $table) => $table->renameColumn('monthly_rent', 'annual_rent'));
        }
        if (Schema::hasColumn('market_rent_periods', 'monthly_rent')) {
            Schema::table('market_rent_periods', fn (Blueprint $table) => $table->renameColumn('monthly_rent', 'annual_rent'));
        }
        if (! Schema::hasColumn('market_rent_periods', 'start_year')) {
            Schema::table('market_rent_periods', function (Blueprint $table): void {
                $table->unsignedSmallInteger('start_year')->nullable();
                $table->unsignedSmallInteger('end_year')->nullable();
            });
        }
        DB::table('market_rent_periods')->orderBy('id')->chunkById(100, function ($periods): void {
            foreach ($periods as $period) {
                if ($period->start_year !== null) {
                    continue;
                }
                $leaseStart = DB::table('markets')->where('id', $period->market_id)->value('lease_start_date');
                $anniversaryMonth = substr($leaseStart, 5, 2);
                $startYear = (int) substr($period->start_month, 0, 4) - (substr($period->start_month, 5, 2) < $anniversaryMonth ? 1 : 0);
                $endYear = $period->end_month === null ? null : (int) substr($period->end_month, 0, 4) - (substr($period->end_month, 5, 2) < $anniversaryMonth ? 1 : 0);
                DB::table('market_rent_periods')->where('id', $period->id)->update([
                    'start_year' => $startYear,
                    'end_year' => $endYear,
                    'notes' => trim(($period->notes ?? '')."\nسجل سابق نُقل إلى الإيجار السنوي مع الاحتفاظ بالمبلغ؛ يرجى مراجعة المبلغ وحالة السداد. الفترة الأصلية: ".$period->start_month.' إلى '.($period->end_month ?? 'مستمرة')),
                ]);
            }
        });
        if (! Schema::hasIndex('market_rent_periods', 'market_rent_periods_market_id_start_year_end_year_index')) {
            Schema::table('market_rent_periods', fn (Blueprint $table) => $table->index(['market_id', 'start_year', 'end_year']));
        }
        if (Schema::hasIndex('market_rent_periods', 'market_rent_periods_market_id_start_month_end_month_index')) {
            Schema::table('market_rent_periods', fn (Blueprint $table) => $table->dropIndex('market_rent_periods_market_id_start_month_end_month_index'));
        }
        if (Schema::hasColumn('market_rent_periods', 'start_month')) {
            Schema::table('market_rent_periods', fn (Blueprint $table) => $table->dropColumn(['start_month', 'end_month']));
        }
        Schema::table('market_rent_periods', fn (Blueprint $table) => $table->unsignedSmallInteger('start_year')->nullable(false)->change());

    }

    public function down(): void
    {
        Schema::table('market_rent_periods', function (Blueprint $table): void {
            $table->string('start_month', 7)->nullable();
            $table->string('end_month', 7)->nullable();
        });
        DB::table('market_rent_periods')->orderBy('id')->chunkById(100, function ($periods): void {
            foreach ($periods as $period) {
                $month = substr(DB::table('markets')->where('id', $period->market_id)->value('lease_start_date'), 5, 2);
                DB::table('market_rent_periods')->where('id', $period->id)->update([
                    'start_month' => $period->start_year.'-'.$month,
                    'end_month' => $period->end_year === null ? null : $period->end_year.'-'.$month,
                ]);
            }
        });
        Schema::table('market_rent_periods', function (Blueprint $table): void {
            $table->index(['market_id', 'start_month', 'end_month']);
        });
        Schema::table('market_rent_periods', function (Blueprint $table): void {
            $table->dropIndex('market_rent_periods_market_id_start_year_end_year_index');
            $table->dropColumn(['start_year', 'end_year']);
            $table->renameColumn('annual_rent', 'monthly_rent');
            $table->string('start_month', 7)->nullable(false)->change();
        });
        Schema::table('markets', function (Blueprint $table): void {
            $table->renameColumn('annual_rent', 'monthly_rent');
        });
    }
};
