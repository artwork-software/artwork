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
        Schema::create('user_calendar_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('project_status')->default(false);
            $table->boolean('options')->default(false);
            $table->boolean('project_management')->default(false);
            $table->boolean('repeating_events')->default(false);
            $table->boolean('work_shifts')->default(false);
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
        Schema::dropIfExists('user_calendar_settings');
    }
};
