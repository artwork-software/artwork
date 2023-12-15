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
        Schema::table('main_positions', function (Blueprint $table): void {
            $table->boolean('is_fixed')->default(false);
        });

        Schema::table('sub_positions', function (Blueprint $table): void {
            $table->boolean('is_fixed')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('main_positions', function (Blueprint $table): void {
            $table->dropColumn('is_fixed');
        });

        Schema::table('sub_positions', function (Blueprint $table): void {
            $table->dropColumn('is_fixed');
        });
    }
};
