<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('markets', function (Blueprint $table): void {
            $table->renameColumn('monthly_rent', 'annual_rent');
        });
        Schema::table('market_rent_periods', function (Blueprint $table): void {
            $table->renameColumn('monthly_rent', 'annual_rent');
            $table->unsignedSmallInteger('start_year')->nullable();
            $table->unsignedSmallInteger('end_year')->nullable();
        });
        DB::table('market_rent_periods')->orderBy('id')->chunkById(100, function ($periods): void {
            foreach ($periods as $period) {
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
        Schema::table('market_rent_periods', function (Blueprint $table): void {
            $table->dropIndex('market_rent_periods_market_id_start_month_end_month_index');
            $table->dropColumn(['start_month', 'end_month']);
            $table->unsignedSmallInteger('start_year')->nullable(false)->change();
            $table->index(['market_id', 'start_year', 'end_year']);
        });
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
            $table->dropIndex('market_rent_periods_market_id_start_year_end_year_index');
            $table->dropColumn(['start_year', 'end_year']);
            $table->renameColumn('annual_rent', 'monthly_rent');
            $table->string('start_month', 7)->nullable(false)->change();
            $table->index(['market_id', 'start_month', 'end_month']);
        });
        Schema::table('markets', function (Blueprint $table): void {
            $table->renameColumn('annual_rent', 'monthly_rent');
        });
    }
};
