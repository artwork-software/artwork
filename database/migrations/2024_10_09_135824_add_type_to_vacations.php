<?php

use Artwork\Modules\Vacation\Enums\Vacation;
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
        Schema::table('vacations', function (Blueprint $table) {
            $table->enum('type', [Vacation::NOT_AVAILABLE->value, Vacation::OFF_WORK->value])
                ->default(Vacation::NOT_AVAILABLE->value);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacations', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
