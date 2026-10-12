<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->decimal('area', 12, 2);
            $table->decimal('annual_rent', 14, 2);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('market_rent_periods', function (Blueprint $table): void {
            $table->foreignId('market_section_id')->nullable()->after('market_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('market_rent_periods', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('market_section_id');
        });
        Schema::dropIfExists('market_sections');
    }
};
