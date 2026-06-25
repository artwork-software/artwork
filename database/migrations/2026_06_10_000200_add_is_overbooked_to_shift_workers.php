<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Markiert eine Zuweisung dauerhaft als Überbuchung (über den geplanten Bedarf hinaus).
     */
    public function up(): void
    {
        Schema::table('shift_workers', function (Blueprint $table): void {
            $table->boolean('is_overbooked')->default(false)->after('shift_qualification_id');
        });
    }

    public function down(): void
    {
        Schema::table('shift_workers', function (Blueprint $table): void {
            $table->dropColumn('is_overbooked');
        });
    }
};
