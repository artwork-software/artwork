<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reihenfolge der Areale. Bestehende Areale behalten ihre bisherige Reihenfolge (nach ID).
 */
return new class extends Migration
{
    public function up(): void
    {
        // wiederholbar, falls ein früherer Lauf nach dem Anlegen der Spalte abgebrochen ist
        if (!Schema::hasColumn('areas', 'position')) {
            Schema::table('areas', function (Blueprint $table): void {
                $table->unsignedInteger('position')->default(0)->after('color');
            });
        }

        DB::table('areas')->update(['position' => DB::raw('id')]);
    }

    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table): void {
            $table->dropColumn('position');
        });
    }
};
