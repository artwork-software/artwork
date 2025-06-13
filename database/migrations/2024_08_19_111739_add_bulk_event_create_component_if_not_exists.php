<?php

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentPermissionEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $bulkBodyComponent = DB::table('components')
            ->where('type', ProjectTabComponentEnum::BULK_EDIT)
            ->first();

        if (!$bulkBodyComponent) {
            DB::table('components')
                ->insert(
                    [
                        'name' => 'Bulk Event Create',
                        'type' => ProjectTabComponentEnum::BULK_EDIT,
                        'data' => json_encode([]),
                        'special' => true,
                        'sidebar_enabled' => false,
                        'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
                    ]
                );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('components')
            ->where('type', 'BulkBody')
            ->delete();
    }
};
