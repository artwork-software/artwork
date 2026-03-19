<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compensation_day_offs', function (Blueprint $table): void {
            $table->dropForeign(['violation_id']);
            $table->unsignedBigInteger('violation_id')->nullable()->change();
            $table->foreign('violation_id')->references('id')->on('shift_rule_violations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('compensation_day_offs', function (Blueprint $table): void {
            $table->dropForeign(['violation_id']);
            $table->unsignedBigInteger('violation_id')->nullable(false)->change();
            $table->foreign('violation_id')->references('id')->on('shift_rule_violations')->cascadeOnDelete();
        });
    }
};
