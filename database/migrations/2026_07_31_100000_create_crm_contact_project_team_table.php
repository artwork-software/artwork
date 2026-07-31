<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_contact_project_team', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crm_contact_id')->constrained('crm_contacts')->cascadeOnDelete();
            $table->json('roles')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'crm_contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_contact_project_team');
    }
};
