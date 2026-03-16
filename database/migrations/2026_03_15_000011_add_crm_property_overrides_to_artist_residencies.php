<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('artist_residencies', 'crm_property_overrides')) {
            Schema::table('artist_residencies', function (Blueprint $table) {
                $table->json('crm_property_overrides')->nullable()->after('position');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('artist_residencies', 'crm_property_overrides')) {
            Schema::table('artist_residencies', function (Blueprint $table) {
                $table->dropColumn('crm_property_overrides');
            });
        }
    }
};
