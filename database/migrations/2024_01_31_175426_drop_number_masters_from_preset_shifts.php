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
        Schema::table('preset_shifts', function (Blueprint $table): void {
            $table->dropColumn('number_masters');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('preset_shifts', function (Blueprint $table): void {
            $table->integer('number_masters')->default(0);
        });
    }
};
