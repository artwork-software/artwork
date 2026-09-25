<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Einladungen aus einem Projekt-Tab legen keinen eigenen CRM-Kontakt mehr an: Der Zugang hängt
 * dann nur an der E-Mail (plus optionalem Namen). CRM-Kontakte entstehen stattdessen über die
 * Komponente „CRM-Kontaktliste“ im freigegebenen Tab.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_accesses', function (Blueprint $table): void {
            $table->dropForeign(['crm_contact_id']);
        });

        Schema::table('external_accesses', function (Blueprint $table): void {
            $table->unsignedBigInteger('crm_contact_id')->nullable()->change();
            $table->foreign('crm_contact_id')->references('id')->on('crm_contacts')->cascadeOnDelete();
            $table->string('name')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('external_accesses', function (Blueprint $table): void {
            $table->dropColumn('name');
        });
    }
};
