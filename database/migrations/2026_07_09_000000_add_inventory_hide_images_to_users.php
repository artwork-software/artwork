<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Per-user preference whether article images are hidden in the inventory
     * overview (both grid and list view) to fit more articles on screen.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('inventory_hide_images')->default(false)->after('inventory_grid_layout');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('inventory_hide_images');
        });
    }
};
