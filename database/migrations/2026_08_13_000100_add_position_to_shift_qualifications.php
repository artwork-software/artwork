<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('shift_qualifications', 'position')) {
            Schema::table('shift_qualifications', function (Blueprint $table): void {
                $table->integer('position')->default(0)->after('available');
            });
        }

        // Bestehende Reihenfolge (bisher created_at) als Startposition übernehmen
        $position = 1;
        foreach (
            DB::table('shift_qualifications')->orderBy('created_at')->orderBy('id')->pluck('id') as $id
        ) {
            DB::table('shift_qualifications')->where('id', $id)->update(['position' => $position++]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('shift_qualifications', 'position')) {
            Schema::table('shift_qualifications', function (Blueprint $table): void {
                $table->dropColumn('position');
            });
        }
    }
};
