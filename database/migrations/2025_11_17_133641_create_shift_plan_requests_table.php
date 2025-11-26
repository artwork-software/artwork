<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shift_plan_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('craft_id')
                ->constrained('crafts')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('week_number'); // 1–53
            $table->unsignedSmallInteger('year');       // z.B. 2025

            $table->string('status', 20)->default('pending'); // pending, approved, rejected

            $table->foreignId('requested_by_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamp('requested_at')->useCurrent();

            $table->foreignId('reviewed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comment')->nullable();

            $table->timestamps();

            // Optionale sinnvolle Indexe:
            $table->index(['craft_id', 'year', 'week_number']);
            $table->index(['status']);
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->boolean('in_workflow')->default(false)->after('is_committed');
            $table->foreignId('current_request_id')
                ->nullable()
                ->after('in_workflow')
                ->constrained('shift_plan_requests')
                ->nullOnDelete();
            $table->text('workflow_rejection_reason')
                ->nullable()
                ->after('current_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['in_workflow', 'current_request_id']);
        });

        Schema::dropIfExists('shift_plan_requests');
    }
};
