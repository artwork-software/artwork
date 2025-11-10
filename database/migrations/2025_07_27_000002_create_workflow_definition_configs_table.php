<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_definition_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_definition_id')
                ->constrained('workflow_definitions')
                ->onDelete('cascade');
            $table->json('config');
            $table->timestamp('deprecated_at')->nullable();
            $table->timestamps();

            $table->index(['workflow_definition_id', 'deprecated_at'], 'workflow_definition_config_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_definition_configs');
    }
};
