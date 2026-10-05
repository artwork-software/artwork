<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Verknüpfung CRM-Kontakt ↔ Projekt je Komponente „CRM-Kontaktliste“. Pro Komponente getrennt,
 * damit mehrere Listen in einem Projekt (z. B. „Anreisende“ und „Technik-Crew“) unabhängig sind.
 * Kontakte, die Externe anlegen, tragen den Zugang als Urheber und bleiben „ungeprüft“, bis der
 * Tab intern bestätigt wird.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_component_crm_contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crm_contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()
                ->constrained('users', indexName: 'pccc_created_by_user_fk')->nullOnDelete();
            $table->foreignId('created_by_external_access_id')->nullable()
                ->constrained('external_accesses', indexName: 'pccc_created_by_external_fk')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()
                ->constrained('users', indexName: 'pccc_reviewed_by_user_fk')->nullOnDelete();
            $table->timestamps();

            $table->unique(['project_id', 'component_id', 'crm_contact_id'], 'pccc_project_component_contact_unique');
        });

        Schema::table('crm_contacts', function (Blueprint $table): void {
            $table->foreignId('created_by_external_access_id')->nullable()->after('entity_id')
                ->constrained('external_accesses', indexName: 'crm_contacts_created_by_external_fk')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('crm_contacts', function (Blueprint $table): void {
            $table->dropForeign('crm_contacts_created_by_external_fk');
            $table->dropColumn('created_by_external_access_id');
        });
        Schema::dropIfExists('project_component_crm_contacts');
    }
};
