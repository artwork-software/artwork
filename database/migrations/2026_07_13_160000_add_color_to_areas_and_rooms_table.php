<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('areas', 'color')) {
            Schema::table('areas', function (Blueprint $table): void {
                $table->string('color', 7)->default('#000000')->after('name');
            });
        }

        if (!Schema::hasColumn('rooms', 'color')) {
            Schema::table('rooms', function (Blueprint $table): void {
                // null = Raum erbt die Farbe seines Areals
                $table->string('color', 7)->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('areas', 'color')) {
            Schema::table('areas', function (Blueprint $table): void {
                $table->dropColumn('color');
            });
        }

        if (Schema::hasColumn('rooms', 'color')) {
            Schema::table('rooms', function (Blueprint $table): void {
                $table->dropColumn('color');
            });
        }
    }
};
