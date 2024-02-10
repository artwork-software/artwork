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
        Schema::create('sage_not_assigned_data', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('sage_id')->nullable();
            $table->string('kto')->nullable();
            $table->string('kst')->nullable();
            $table->string('cost_center')->nullable();
            $table->string('description')->nullable();
            $table->string('amount')->nullable();

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
        Schema::dropIfExists('sage_not_assigned_data');
    }
};
