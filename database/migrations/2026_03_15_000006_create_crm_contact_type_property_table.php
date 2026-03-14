<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_contact_type_property', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_contact_type_id')->constrained('crm_contact_types')->cascadeOnDelete();
            $table->foreignId('crm_property_id')->constrained('crm_properties')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->unique(['crm_contact_type_id', 'crm_property_id'], 'crm_type_property_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_contact_type_property');
    }
};
