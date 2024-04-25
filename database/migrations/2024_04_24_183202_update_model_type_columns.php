<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');

        DB::table($tableNames['model_has_permissions'])
            ->where('model_type', '=', 'App\Models\User')
            ->update(['model_type' => 'Artwork\Modules\User\Models\User']);

        DB::table($tableNames['model_has_roles'])
            ->where('model_type', '=', 'App\Models\User')
            ->update(['model_type' => 'Artwork\Modules\User\Models\User']);

        DB::table(config('model_changes_history.stores.database.table', 'model_changes_history'))
            ->where('model_type', '=', 'App\Models\User')
            ->update(['model_type' => 'Artwork\Modules\User\Models\User']);

        DB::table('notifications')
            ->where('notifiable_type', '=', 'App\Models\User')
            ->update(['notifiable_type' => 'Artwork\Modules\User\Models\User']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');

        DB::table($tableNames['model_has_permissions'])
            ->where('model_type', '=', 'Artwork\Modules\User\Models\User')
            ->update(['model_type' => 'App\Models\User']);

        DB::table($tableNames['model_has_roles'])
            ->where('model_type', '=', 'Artwork\Modules\User\Models\User')
            ->update(['model_type' => 'App\Models\User']);

        DB::table(config('model_changes_history.stores.database.table', 'model_changes_history'))
            ->where('model_type', '=', 'Artwork\Modules\User\Models\User')
            ->update(['model_type' => 'App\Models\User']);

        DB::table('notifications')
            ->where('notifiable_type', '=', 'Artwork\Modules\User\Models\User')
            ->update(['notifiable_type' => 'App\Models\User']);
    }
};
