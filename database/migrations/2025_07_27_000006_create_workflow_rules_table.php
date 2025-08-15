<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_type');
            $table->decimal('individual_number_value', 10, 2)->nullable();
            $table->string('warning_color', 7)->default('#ff0000'); // hex color
            $table->boolean('is_active')->default(true);
            $table->json('configuration')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['trigger_type', 'is_active'], 'workflow_rule_index');
            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_rules');
    }
};
