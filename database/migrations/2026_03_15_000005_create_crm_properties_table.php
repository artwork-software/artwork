<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_properties', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_property_group_id')->constrained('crm_property_groups')->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->json('select_values')->nullable();
            $table->boolean('is_required')->default(false);
            $table->string('tooltip_text')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->boolean('show_in_list')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_properties');
    }
};
