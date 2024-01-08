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
        Schema::table('freelancers', function (Blueprint $table): void {
            $table->boolean('can_work_shifts')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('freelancers', function (Blueprint $table): void {
            $table->dropColumn('can_work_shifts');
        });
    }
};
