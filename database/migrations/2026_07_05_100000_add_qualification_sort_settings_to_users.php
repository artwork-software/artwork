<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Per-user shift plan preference: group workers within a craft by their
     * shift qualifications (small collapsible headings per qualification).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('sort_workers_by_qualification')->default(true)->after('shift_plan_user_sort_by_id');
            $table->json('closed_qualification_groups')->nullable()->after('sort_workers_by_qualification');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['sort_workers_by_qualification', 'closed_qualification_groups']);
        });
    }
};
