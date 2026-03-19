<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_rule_violations', function (Blueprint $table): void {
            $table->text('ignore_reason')->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('shift_rule_violations', function (Blueprint $table): void {
            $table->dropColumn('ignore_reason');
        });
    }
};
