<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_article_statuses', function (Blueprint $table) {
            $table->string('color')->nullable()->after('name')->comment('Color of the article status');
        });

        // Update existing records to set a default color if needed as hex color
        /*
         * 'Einsatzbereit': { color: 'text-green-600', icon: '🟢', bgColor: 'bg-green-100', borderColor: 'border-green-300' },
    'Defekt': { color: 'text-red-500', icon: '❌', bgColor: 'bg-red-100', borderColor: 'border-red-300' },
    'Ausgesondert': { color: 'text-yellow-500', icon: '⚠️', bgColor: 'bg-yellow-100', borderColor: 'border-yellow-300' },
    'Nicht auffindbar': { color: 'text-gray-500', icon: '❓', bgColor: 'bg-gray-100', borderColor: 'border-gray-300' },
    'fest verbaut': { color: 'text-blue-500', icon: '📦', bgColor: 'bg-blue-100', borderColor: 'border-blue-300' }
         */

        DB::table('inventory_article_statuses')->update([
            'color' => DB::raw("CASE
                WHEN name = 'Einsatzbereit' THEN '#16A34A' -- text-green-600
                WHEN name = 'Defekt' THEN '#EF4444' -- text-red-500
                WHEN name = 'Ausgesondert' THEN '#F59E0B' -- text-yellow-500
                WHEN name = 'Nicht auffindbar' THEN '#6B7280' -- text-gray-500
                WHEN name = 'fest verbaut' THEN '#3B82F6' -- text-blue-500
                ELSE NULL
            END")
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_article_statuses', function (Blueprint $table) {
            //
        });
    }
};
