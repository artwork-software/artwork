<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compensation_day_offs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('violation_id');
            $table->decimal('value', 2, 1);
            $table->date('deadline');
            $table->date('granted_date')->nullable();
            $table->unsignedBigInteger('granted_by')->nullable();
            $table->dateTime('granted_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('violation_id')->references('id')->on('shift_rule_violations')->cascadeOnDelete();
            $table->foreign('granted_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['user_id', 'granted_date']);
            $table->index(['user_id', 'granted_at']);
            $table->index(['deadline']);
        });

        // Migrate existing compensation data to compensation_day_offs
        $violations = \Illuminate\Support\Facades\DB::table('shift_rule_violations')
            ->whereNotNull('compensation_days')
            ->where('compensation_days', '>', 0)
            ->get();

        foreach ($violations as $violation) {
            $totalDays = (float) $violation->compensation_days;
            $records = [];

            while ($totalDays >= 1.0) {
                $records[] = 1.0;
                $totalDays -= 1.0;
            }
            if ($totalDays >= 0.5) {
                $records[] = 0.5;
            }

            foreach ($records as $value) {
                \Illuminate\Support\Facades\DB::table('compensation_day_offs')->insert([
                    'user_id' => $violation->user_id,
                    'violation_id' => $violation->id,
                    'value' => $value,
                    'deadline' => $violation->compensation_deadline,
                    'granted_date' => $violation->compensation_granted_at ? now()->toDateString() : null,
                    'granted_by' => $violation->compensation_granted_by,
                    'granted_at' => $violation->compensation_granted_at,
                    'reason' => $violation->compensation_reason,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('compensation_day_offs');
    }
};
