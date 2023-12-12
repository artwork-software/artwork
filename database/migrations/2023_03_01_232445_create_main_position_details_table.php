<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('main_position_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger("main_position_id");
            $table->unsignedBigInteger("column_id");
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
        Schema::dropIfExists('main_position_details');
    }

};
