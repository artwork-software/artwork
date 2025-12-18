<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_position_rows', function (Blueprint $table): void {
            $table->unsignedInteger('order')->default(0)->after('position');
            $table->index(['sub_position_id', 'order'], 'sub_position_rows_sub_position_id_order_index');
        });

        // keep existing ordering; `position` is the historical ordering column
        DB::table('sub_position_rows')->update([
            'order' => DB::raw('position'),
        ]);
    }

    public function down(): void
    {
        Schema::table('sub_position_rows', function (Blueprint $table): void {
            $table->dropIndex('sub_position_rows_sub_position_id_order_index');
            $table->dropColumn('order');
        });
    }
};
