<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zeitpunkt, zu dem ein Tagesstatus zuletzt seine Art oder Tageshälfte gewechselt hat. Grundlage der
     * Regel "Ankündigungsfrist freier Tage": ein bestehender Eintrag, der erst später auf "Frei"
     * umgestellt wird, gilt ab der Umstellung als angekündigt und nicht ab seiner Anlage.
     */
    public function up(): void
    {
        Schema::table('vacations', function (Blueprint $table): void {
            $table->timestamp('status_changed_at')->nullable()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('vacations', function (Blueprint $table): void {
            $table->dropColumn('status_changed_at');
        });
    }
};
