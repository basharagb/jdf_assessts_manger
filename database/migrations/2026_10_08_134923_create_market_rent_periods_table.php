<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('market_rent_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->string('start_month', 7);
            $table->string('end_month', 7)->nullable();
            $table->decimal('monthly_rent', 14, 2)->nullable();
            $table->string('status', 16)->default('paid');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['market_id', 'start_month', 'end_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_rent_periods');
    }
};
