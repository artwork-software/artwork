<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_definition_config_id')
                ->constrained('workflow_definition_configs')
                ->onDelete('cascade');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('current_place')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['workflow_definition_config_id', 'completed_at'], 'workflow_instance_completed_at_index');
            $table->index(['current_place']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_instances');
    }
};
