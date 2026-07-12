<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sage_booking_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('type'); // import | delete
            $table->string('source')->nullable(); // daily_import | full_import | specific_day_import | delete_bookings
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('criteria')->nullable();
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('deleted_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('created_at');
        });

        Schema::create('sage_booking_log_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sage_booking_log_id')
                ->constrained('sage_booking_logs')
                ->cascadeOnDelete();
            $table->string('action'); // created | updated | deleted
            $table->string('target_type'); // assigned | not_assigned
            $table->unsignedBigInteger('sage_record_id')->nullable();

            // Snapshot of the booking fields at the time of the action.
            $table->unsignedBigInteger('sage_id')->nullable();
            $table->unsignedBigInteger('tan')->nullable();
            $table->unsignedBigInteger('periode')->nullable();
            $table->string('kto_haben')->nullable();
            $table->string('kreditor')->nullable();
            $table->string('buchungstext')->nullable();
            $table->decimal('buchungsbetrag', 12, 2)->nullable();
            $table->string('belegnummer')->nullable();
            $table->string('belegdatum')->nullable();
            $table->string('kto_soll')->nullable();
            $table->string('sa_kto')->nullable();
            $table->string('kst_traeger')->nullable();
            $table->string('kst_stelle')->nullable();
            $table->string('buchungsdatum')->nullable();
            $table->boolean('is_collective_booking')->default(false);
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('column_cell_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('sage_id');
            $table->index('kst_traeger');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sage_booking_log_entries');
        Schema::dropIfExists('sage_booking_logs');
    }
};
