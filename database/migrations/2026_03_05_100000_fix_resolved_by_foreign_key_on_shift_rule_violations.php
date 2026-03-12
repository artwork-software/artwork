<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_rule_violations', function (Blueprint $table): void {
            $table->dropForeign(['resolved_by']);
        });

        Schema::table('shift_rule_violations', function (Blueprint $table): void {
            $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shift_rule_violations', function (Blueprint $table): void {
            $table->dropForeign(['resolved_by']);
        });

        Schema::table('shift_rule_violations', function (Blueprint $table): void {
            $table->foreign('resolved_by')->references('id')->on('users');
        });
    }
};
