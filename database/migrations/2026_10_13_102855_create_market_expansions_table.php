<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_expansions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_section_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('start_year');
            $table->decimal('added_area', 12, 2)->nullable();
            $table->decimal('added_annual_rent', 14, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_expansions');
    }
};
