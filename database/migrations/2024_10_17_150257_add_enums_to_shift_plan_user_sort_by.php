<?php

use Artwork\Modules\Event\Enum\ShiftPlanWorkerSortEnum;
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
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'shift_plan_user_sort_by')) {
                $table->dropColumn('shift_plan_user_sort_by');
            }

            if (!Schema::hasColumn('users', 'shift_plan_user_sort_by')) {
                $table->string('shift_plan_user_sort_by')
                    ->after('show_crafts')
                    ->default(null)
                    ->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'shift_plan_user_sort_by')) {
                $table->dropColumn('shift_plan_user_sort_by');
            }
        });
    }
};
