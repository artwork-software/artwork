<?php

use Artwork\Modules\Event\Enum\ShiftPlanWorkerSortEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table
                ->enum(
                    'shift_plan_user_sort_by',
                    [
                        ShiftPlanWorkerSortEnum::ALPHABETICALLY_ASCENDING_FIRST_NAME->name,
                        ShiftPlanWorkerSortEnum::ALPHABETICALLY_DESCENDING_FIRST_NAME->name,
                        ShiftPlanWorkerSortEnum::ALPHABETICALLY_ASCENDING_LAST_NAME->name,
                        ShiftPlanWorkerSortEnum::ALPHABETICALLY_DESCENDING_LAST_NAME->name,
                    ]
                )
                ->after('show_crafts')
                ->default(null)
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('shift_plan_user_sort_by');
        });
    }
};
