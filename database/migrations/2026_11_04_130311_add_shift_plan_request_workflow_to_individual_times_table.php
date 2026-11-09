<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Individuelle Zeiten laufen wie Schichten durch die Dienstplan-Freigabe:
     *  - in_workflow / current_request_id: Teil einer offenen Freigabe-Anfrage
     *  - is_committed / committing_user_id: freigegeben
     * Die Verlaufstabelle hält fest, welche Zeiten zu welcher Anfrage gehörten (analog
     * shift_plan_request_shifts), auch nachdem der Live-Zeiger bei der Freigabe gelöst wurde.
     */
    public function up(): void
    {
        Schema::table('individual_times', function (Blueprint $table): void {
            $table->boolean('in_workflow')->default(false)->after('break_minutes');
            $table->boolean('is_committed')->default(false)->after('in_workflow');
            $table->foreignId('current_request_id')->nullable()->after('is_committed')
                ->constrained('shift_plan_requests')->nullOnDelete();
            $table->foreignId('committing_user_id')->nullable()->after('current_request_id')
                ->constrained('users')->nullOnDelete();
        });

        Schema::create('shift_plan_request_individual_times', function (Blueprint $table): void {
            $table->id();
            // Kurze Constraint-Namen: die automatisch erzeugten überschreiten MySQLs 64 Zeichen
            $table->foreignId('shift_plan_request_id')
                ->constrained('shift_plan_requests', indexName: 'spr_individual_times_request_fk')
                ->cascadeOnDelete();
            $table->foreignId('individual_time_id')
                ->constrained('individual_times', indexName: 'spr_individual_times_time_fk')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['shift_plan_request_id', 'individual_time_id'], 'spr_individual_times_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_plan_request_individual_times');

        Schema::table('individual_times', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('committing_user_id');
            $table->dropConstrainedForeignId('current_request_id');
            $table->dropColumn(['in_workflow', 'is_committed']);
        });
    }
};
