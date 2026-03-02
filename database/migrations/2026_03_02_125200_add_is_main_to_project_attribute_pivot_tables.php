<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_project', function (Blueprint $table) {
            $table->boolean('is_main')->default(false)->after('category_id');
        });

        Schema::table('genre_project', function (Blueprint $table) {
            $table->boolean('is_main')->default(false)->after('genre_id');
        });

        Schema::table('project_sector', function (Blueprint $table) {
            $table->boolean('is_main')->default(false)->after('sector_id');
        });
    }

    public function down(): void
    {
        Schema::table('category_project', function (Blueprint $table) {
            $table->dropColumn('is_main');
        });

        Schema::table('genre_project', function (Blueprint $table) {
            $table->dropColumn('is_main');
        });

        Schema::table('project_sector', function (Blueprint $table) {
            $table->dropColumn('is_main');
        });
    }
};
