<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whats_app_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->json('additional_numbers')->nullable();
            $table->timestamps();
        });
        Schema::create('contract_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->date('lease_end_date');
            $table->string('recipient', 16);
            $table->string('status', 16)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->string('message_id')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['market_id', 'lease_end_date', 'recipient']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_reminders');
        Schema::dropIfExists('whats_app_settings');
    }
};
