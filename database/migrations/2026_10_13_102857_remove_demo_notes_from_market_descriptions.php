<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Removes the seeded sentences about demo values and photo sources from market notes,
 * keeping everything else the user wrote.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('markets')->whereNotNull('description')->get(['id', 'description']) as $market) {
            $description = preg_replace(['/\s*[^.]*بيانات ديمو\./u', '/\s*الصورة [^.]*\./u'], '', $market->description);
            if ($description !== $market->description) {
                DB::table('markets')->where('id', $market->id)->update(['description' => trim($description) ?: null]);
            }
        }
    }

    /**
     * The removed sentences are not kept, so there is nothing to restore.
     */
    public function down(): void {}
};
