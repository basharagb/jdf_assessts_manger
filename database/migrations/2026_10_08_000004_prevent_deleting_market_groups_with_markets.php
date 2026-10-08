<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('markets', function (Blueprint $table): void {
            $table->dropForeign(['market_group_id']);
            $table->foreign('market_group_id')->references('id')->on('market_groups')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('markets', function (Blueprint $table): void {
            $table->dropForeign(['market_group_id']);
            $table->foreign('market_group_id')->references('id')->on('market_groups')->cascadeOnDelete();
        });
    }
};
