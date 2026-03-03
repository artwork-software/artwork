<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('category_project', 'is_main')) {
            Schema::table('category_project', function (Blueprint $table) {
                $table->boolean('is_main')->default(false)->after('category_id');
            });
        }

        if (!Schema::hasColumn('genre_project', 'is_main')) {
            Schema::table('genre_project', function (Blueprint $table) {
                $table->boolean('is_main')->default(false)->after('genre_id');
            });
        }

        if (!Schema::hasColumn('project_sector', 'is_main')) {
            Schema::table('project_sector', function (Blueprint $table) {
                $table->boolean('is_main')->default(false)->after('sector_id');
            });
        }
    }

    public function down(): void
    {
        // Columns are managed by the original migration 2026_03_02_125200
    }
};
