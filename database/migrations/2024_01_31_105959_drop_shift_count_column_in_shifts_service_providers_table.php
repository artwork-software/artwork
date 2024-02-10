<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('shifts_service_providers', function (Blueprint $table): void {
            $table->dropColumn('shift_count');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('shifts_service_providers', function (Blueprint $table): void {
            $table
                ->unsignedBigInteger('shift_count')
                ->after('shift_qualification_id');
        });
    }
};
