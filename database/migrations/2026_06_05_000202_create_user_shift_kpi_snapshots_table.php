<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * DP-18: Persistierter Spielzeit-Snapshot der getrackten Kennzahlen je User.
     * Wird nächtlich per Job upgesertet (Voll-Recompute). Pro Spielzeit (season_start/_end)
     * eine Zeile -> spätere Auswertung vergangener Spielzeiten möglich.
     */
    public function up(): void
    {
        Schema::create('user_shift_kpi_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('season_start');
            $table->date('season_end');
            $table->unsignedSmallInteger('calendar_year');

            // freie Sonntage (Varianten)
            $table->unsignedInteger('free_sundays_sat_mon_half1')->default(0);
            $table->unsignedInteger('free_sundays_sat_mon_half2')->default(0);
            $table->unsignedInteger('free_sundays_and_saturdays_season')->default(0);
            $table->unsignedInteger('free_sundays_calendar_year')->default(0);
            $table->unsignedInteger('free_sundays_per_season')->default(0);

            // 1,5-Tage-Kombinationen je Hälfte
            $table->unsignedInteger('one_and_half_combos_half1')->default(0);
            $table->unsignedInteger('one_and_half_combos_half2')->default(0);

            // gewährte halbe freie Tage je Hälfte
            $table->unsignedInteger('granted_half_free_days_half1')->default(0);
            $table->unsignedInteger('granted_half_free_days_half2')->default(0);

            // freie Tage in den ersten 26 Wochen (gezählt)
            $table->decimal('days_off_first_26_weeks_count', 6, 1)->default(0);

            // Urlaub (gewährt im Kalenderjahr, ganze + halbe als Dezimaltage)
            $table->decimal('granted_vacation_days_year', 6, 1)->default(0);

            $table->timestamp('recalculated_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'season_start', 'season_end'], 'user_season_kpi_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_shift_kpi_snapshots');
    }
};
