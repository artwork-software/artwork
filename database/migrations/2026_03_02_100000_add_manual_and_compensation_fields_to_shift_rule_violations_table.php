<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_rule_violations', function (Blueprint $table): void {
            // Make shift_id nullable for manual violations (no shift attached)
            $table->unsignedBigInteger('shift_id')->nullable()->change();

            // Manual violation fields
            $table->text('reason')->nullable()->after('violation_data');
            $table->boolean('is_manual')->default(false)->after('reason');
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->after('is_manual')
                ->constrained('users')
                ->nullOnDelete();

            // Compensation fields
            $table->decimal('compensation_days', 4, 1)->nullable()->after('created_by_user_id');
            $table->date('compensation_deadline')->nullable()->after('compensation_days');
            $table->text('compensation_reason')->nullable()->after('compensation_deadline');
            $table->dateTime('compensation_granted_at')->nullable()->after('compensation_reason');
            $table->foreignId('compensation_granted_by')
                ->nullable()
                ->after('compensation_granted_at')
                ->constrained('users')
                ->nullOnDelete();

            // Parent violation reference (for deadline expiry violations)
            $table->foreignId('parent_violation_id')
                ->nullable()
                ->after('compensation_granted_by')
                ->constrained('shift_rule_violations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shift_rule_violations', function (Blueprint $table): void {
            $table->dropForeign(['parent_violation_id']);
            $table->dropForeign(['compensation_granted_by']);
            $table->dropForeign(['created_by_user_id']);

            $table->dropColumn([
                'reason',
                'is_manual',
                'created_by_user_id',
                'compensation_days',
                'compensation_deadline',
                'compensation_reason',
                'compensation_granted_at',
                'compensation_granted_by',
                'parent_violation_id',
            ]);
        });
    }
};
