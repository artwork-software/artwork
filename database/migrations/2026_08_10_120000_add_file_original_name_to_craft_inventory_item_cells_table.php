<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Upload cells store the on-disk filename in cell_value. Since that name is
     * now a hash, the readable name needs a home of its own. Existing rows stay
     * NULL and fall back to cell_value, which for them is the original name.
     */
    public function up(): void
    {
        Schema::table('craft_inventory_item_cells', function (Blueprint $table): void {
            $table->string('file_original_name')->nullable()->after('cell_value');
        });
    }

    public function down(): void
    {
        Schema::table('craft_inventory_item_cells', function (Blueprint $table): void {
            $table->dropColumn('file_original_name');
        });
    }
};
