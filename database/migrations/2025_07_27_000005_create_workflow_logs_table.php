<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')
                ->constrained('workflow_instances')
                ->onDelete('cascade');
            $table->string('transition')->nullable();
            $table->string('from_place')->nullable();
            $table->string('to_place');
            $table->json('metadata')->nullable();
            $table->timestamp('triggered_at');
            $table->timestamps();

            $table->index(['workflow_instance_id', 'triggered_at'], 'workflow_log_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_logs');
    }
};
