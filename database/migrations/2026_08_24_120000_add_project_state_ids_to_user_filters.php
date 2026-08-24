<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('user_filters', 'project_state_ids')) {
            Schema::table('user_filters', function (Blueprint $table): void {
                $table->json('project_state_ids')->nullable()->after('craft_ids');
            });
        }

        if (!Schema::hasColumn('user_filter_templates', 'project_state_ids')) {
            Schema::table('user_filter_templates', function (Blueprint $table): void {
                $table->json('project_state_ids')->nullable()->after('craft_ids');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('user_filters', 'project_state_ids')) {
            Schema::table('user_filters', function (Blueprint $table): void {
                $table->dropColumn('project_state_ids');
            });
        }

        if (Schema::hasColumn('user_filter_templates', 'project_state_ids')) {
            Schema::table('user_filter_templates', function (Blueprint $table): void {
                $table->dropColumn('project_state_ids');
            });
        }
    }
};
