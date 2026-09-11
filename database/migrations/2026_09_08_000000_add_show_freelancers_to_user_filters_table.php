<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Schichtplan-Personenfilter "Freelancer einbinden": lag bisher nur im Frontend (ging beim Neuladen
// verloren) und liegt jetzt wie die übrigen Personenfilter auf user_filters (filter_type shift_filter).
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('user_filters', 'show_freelancers')) {
            return;
        }

        Schema::table('user_filters', function (Blueprint $table): void {
            $table->boolean('show_freelancers')->default(true);
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('user_filters', 'show_freelancers')) {
            return;
        }

        Schema::table('user_filters', function (Blueprint $table): void {
            $table->dropColumn('show_freelancers');
        });
    }
};
