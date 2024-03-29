<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('column_sub_position_row', function (Blueprint $table): void {
            $table->id();
            $table->bigInteger('column_id');
            $table->bigInteger('sub_position_row_id');
            $table->string('value')->nullable();
            $table->boolean('commented')->default(false);
            $table->bigInteger('linked_money_source_id')->nullable();
            $table->string('linked_type')->nullable();
            $table->string('verified_value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('column_subposition_row');
    }
};
