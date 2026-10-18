<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Überstunden werden auch ohne aktive Überstundenregel geführt (Kontoprinzip) – dann ohne Frist.
     */
    public function up(): void
    {
        Schema::table('user_overtimes', function (Blueprint $table): void {
            $table->date('deadline')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Einträge ohne Frist gibt es vor dieser Migration nicht: sie entstehen beim nächsten Recompute neu
        DB::table('user_overtimes')->whereNull('deadline')->delete();

        Schema::table('user_overtimes', function (Blueprint $table): void {
            $table->date('deadline')->nullable(false)->change();
        });
    }
};
