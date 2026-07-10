<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $baselineTimestamp = now();

        DB::table('committed_shift_changes')
            ->whereNull('acknowledged_at')
            ->update([
                'acknowledged_at' => $baselineTimestamp,
                'updated_at' => $baselineTimestamp,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally irreversible: later acknowledgements cannot safely be distinguished
        // from rows baselined when the weekly status indicator was introduced.
    }
};
