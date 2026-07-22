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
        Schema::table('user_contracts', function (Blueprint $table): void {
            $table->boolean('use_three_month_average_for_target_reduction')
                ->default(false)
                ->after('compensation_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_contracts', function (Blueprint $table): void {
            $table->dropColumn('use_three_month_average_for_target_reduction');
        });
    }
};
