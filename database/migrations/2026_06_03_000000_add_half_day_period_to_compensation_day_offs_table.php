<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compensation_day_offs', function (Blueprint $table): void {
            // Half day period for half compensation days (value < 1.0): 'morning' | 'afternoon' | null
            $table->string('half_day_period', 20)->nullable()->after('value');
        });
    }

    public function down(): void
    {
        Schema::table('compensation_day_offs', function (Blueprint $table): void {
            $table->dropColumn('half_day_period');
        });
    }
};
