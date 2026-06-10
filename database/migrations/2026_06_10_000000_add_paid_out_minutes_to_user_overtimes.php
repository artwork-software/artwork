<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Teilauszahlungen je Tages-Eintrag: ausgezahlte Minuten werden pro Eintrag mitgeführt,
     * damit der idempotente Recompute aus den Buchungen Auszahlungen nicht wieder zurücksetzt.
     */
    public function up(): void
    {
        Schema::table('user_overtimes', function (Blueprint $table): void {
            $table->unsignedInteger('paid_out_minutes')->default(0)->after('remaining_minutes');
        });

        // Bestehende voll ausgebuchte Einträge (altes bookOut) übernehmen.
        DB::table('user_overtimes')
            ->where('status', 'paid_out')
            ->update(['paid_out_minutes' => DB::raw('minutes')]);
    }

    public function down(): void
    {
        Schema::table('user_overtimes', function (Blueprint $table): void {
            $table->dropColumn('paid_out_minutes');
        });
    }
};
