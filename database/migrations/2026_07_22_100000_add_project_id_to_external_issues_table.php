<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('external_issues', 'project_id')) {
            return;
        }

        Schema::table('external_issues', function (Blueprint $table): void {
            $table->foreignId('project_id')
                ->nullable()
                ->after('received_by_id')
                ->constrained('projects')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('external_issues', 'project_id')) {
            return;
        }

        Schema::table('external_issues', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('project_id');
        });
    }
};
