<?php

use Artwork\Modules\Budget\Services\TableColumnOrderService;
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
        Schema::create('table_column_orders', function (Blueprint $table) {
            $table->id();
            //values are also translation keys in de.json and en.json
            $table->tinyText('display_text');
            $table->unsignedTinyInteger('position');
            $table->timestamps();
        });

        /** @var TableColumnOrderService $tableColumnOrderService */
        $tableColumnOrderService = app()->make(TableColumnOrderService::class);

        $tableColumnOrderService->create(['display_text' => 'Column 1', 'position' => 0]);
        $tableColumnOrderService->create(['display_text' => 'Column 2', 'position' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_column_orders');
    }
};
