<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artist_residencies', function (Blueprint $table) {
            $table->integer('breakfast_count')->default(0)->after('additional_daily_allowance');
            $table->double('breakfast_deduction_per_day', 8, 2)->default(5.60)->after('breakfast_count');
        });
    }

    public function down(): void
    {
        Schema::table('artist_residencies', function (Blueprint $table) {
            $table->dropColumn(['breakfast_count', 'breakfast_deduction_per_day']);
        });
    }
};
