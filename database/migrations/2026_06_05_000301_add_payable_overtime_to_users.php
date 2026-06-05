<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * DP-18 Stufe 2: nächtlich ermittelter "auszuzahlend"-Betrag (Minuten) am User.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedInteger('payable_overtime_minutes')->default(0)->after('work_time_balance');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('payable_overtime_minutes');
        });
    }
};
