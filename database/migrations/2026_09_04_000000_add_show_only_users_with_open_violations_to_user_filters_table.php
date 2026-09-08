<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Schichtplan-Personenfilter "nur Personen mit offenen Regelverstößen": liegt wie die übrigen
// Schichtplan-Filter auf user_filters (filter_type shift_filter / shift_daily_filter).
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('user_filters', 'show_only_users_with_open_violations')) {
            return;
        }

        Schema::table('user_filters', function (Blueprint $table): void {
            $table->boolean('show_only_users_with_open_violations')->default(false);
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('user_filters', 'show_only_users_with_open_violations')) {
            return;
        }

        Schema::table('user_filters', function (Blueprint $table): void {
            $table->dropColumn('show_only_users_with_open_violations');
        });
    }
};
