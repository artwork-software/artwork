<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_instance_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')
                ->constrained('workflow_instances')
                ->onDelete('cascade');
            $table->json('data');
            $table->timestamp('deprecated_at')->nullable();
            $table->timestamps();

            $table->index(['workflow_instance_id', 'deprecated_at'], 'workflow_instance_data_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_instance_data');
    }
};
