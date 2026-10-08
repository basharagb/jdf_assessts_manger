<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('accent', 24)->default('#2563eb');
            $table->timestamps();
        });

        Schema::create('markets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('market_group_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->decimal('area', 12, 2);
            $table->string('lessor');
            $table->decimal('monthly_rent', 14, 2);
            $table->date('lease_start_date');
            $table->date('lease_end_date')->nullable();
            $table->string('image_path')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['market_group_id', 'is_active']);
            $table->index(['lease_start_date', 'lease_end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('markets');
        Schema::dropIfExists('market_groups');
    }
};
