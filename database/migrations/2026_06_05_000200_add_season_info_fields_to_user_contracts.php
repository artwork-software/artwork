<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Rubrik "Spielzeitbezogene Infodaten" (DP-18): pro Parameter ein Aktiv-Flag + X-Wert.
     * Die bestehenden Felder free_sundays_per_season / days_off_first_26_weeks bekommen
     * je ein Aktiv-Flag. Spiegelung auf user_contract_assigns (per-User-Override).
     */
    private array $tables = ['user_contracts', 'user_contract_assigns'];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $after = 'days_off_first_26_weeks';

                $table->boolean('free_sundays_per_season_active')->default(false)->after($after);
                $table->boolean('days_off_first_26_weeks_active')->default(false)
                    ->after('free_sundays_per_season_active');

                $table->unsignedInteger('free_sundays_sat_mon_per_half')->default(0)
                    ->after('days_off_first_26_weeks_active');
                $table->boolean('free_sundays_sat_mon_per_half_active')->default(false)
                    ->after('free_sundays_sat_mon_per_half');

                $table->unsignedInteger('free_sundays_and_saturdays_per_season')->default(0)
                    ->after('free_sundays_sat_mon_per_half_active');
                $table->boolean('free_sundays_and_saturdays_per_season_active')->default(false)
                    ->after('free_sundays_and_saturdays_per_season');

                $table->unsignedInteger('free_sundays_per_calendar_year')->default(0)
                    ->after('free_sundays_and_saturdays_per_season_active');
                $table->boolean('free_sundays_per_calendar_year_active')->default(false)
                    ->after('free_sundays_per_calendar_year');

                $table->unsignedInteger('one_and_half_day_combinations')->default(0)
                    ->after('free_sundays_per_calendar_year_active');
                $table->boolean('one_and_half_day_combinations_active')->default(false)
                    ->after('one_and_half_day_combinations');

                $table->unsignedInteger('annual_vacation_days')->default(0)
                    ->after('one_and_half_day_combinations_active');
            });
        }
    }

    public function down(): void
    {
        $columns = [
            'free_sundays_per_season_active',
            'days_off_first_26_weeks_active',
            'free_sundays_sat_mon_per_half',
            'free_sundays_sat_mon_per_half_active',
            'free_sundays_and_saturdays_per_season',
            'free_sundays_and_saturdays_per_season_active',
            'free_sundays_per_calendar_year',
            'free_sundays_per_calendar_year_active',
            'one_and_half_day_combinations',
            'one_and_half_day_combinations_active',
            'annual_vacation_days',
        ];

        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
