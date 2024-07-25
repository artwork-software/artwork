<?php

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
        Schema::table('column_sub_position_row', static function (Blueprint $table) {
            $table->dropForeign('column_sub_position_row_column_id_foreign');
            $table->dropForeign('column_sub_position_row_linked_money_source_id_foreign');
            $table->dropForeign('column_sub_position_row_sub_position_row_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
