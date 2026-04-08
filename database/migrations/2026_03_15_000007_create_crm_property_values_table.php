<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_property_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_contact_id')->constrained('crm_contacts')->cascadeOnDelete();
            $table->foreignId('crm_property_id')->constrained('crm_properties')->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['crm_contact_id', 'crm_property_id'], 'crm_contact_property_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_property_values');
    }
};
