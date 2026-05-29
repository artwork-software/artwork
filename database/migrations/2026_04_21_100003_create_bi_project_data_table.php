<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bi_project_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained('projects')->cascadeOnDelete();
            $table->enum('visitor_mode', ['total', 'per_event'])->default('total');
            $table->unsignedInteger('visitors_total')->nullable();
            $table->enum('sold_tickets_mode', ['total', 'per_event'])->default('total');
            $table->unsignedInteger('sold_tickets_total')->nullable();
            $table->enum('revenue_mode', ['total', 'per_event'])->default('total');
            $table->decimal('revenue_total', 12, 2)->nullable();
            $table->boolean('is_new_production')->default(false);
            $table->boolean('is_co_production')->default(false);
            $table->boolean('is_own_production')->default(false);
            $table->boolean('is_germany_premiere')->default(false);
            $table->date('premiere_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bi_project_data');
    }
};
