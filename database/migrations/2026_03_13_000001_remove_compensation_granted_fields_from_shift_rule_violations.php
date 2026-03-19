<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_rule_violations', function (Blueprint $table): void {
            $table->dropForeign(['compensation_granted_by']);
            $table->dropColumn(['compensation_granted_at', 'compensation_granted_by']);
        });
    }

    public function down(): void
    {
        Schema::table('shift_rule_violations', function (Blueprint $table): void {
            $table->dateTime('compensation_granted_at')->nullable();
            $table->unsignedBigInteger('compensation_granted_by')->nullable();
            $table->foreign('compensation_granted_by')->references('id')->on('users')->nullOnDelete();
        });
    }
};
