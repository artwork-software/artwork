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
        Schema::table('shifts_freelancers', function (Blueprint $table): void {
            $table->dropColumn('is_master');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('shifts_freelancers', function (Blueprint $table): void {
            $table->boolean('is_master')->default(false);
        });
    }
};
