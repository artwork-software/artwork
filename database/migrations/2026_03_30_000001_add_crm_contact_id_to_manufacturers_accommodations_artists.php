<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('manufacturers', 'crm_contact_id')) {
            Schema::table('manufacturers', function (Blueprint $table): void {
                $table->foreignId('crm_contact_id')
                    ->nullable()
                    ->constrained('crm_contacts')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('accommodations', 'crm_contact_id')) {
            Schema::table('accommodations', function (Blueprint $table): void {
                $table->foreignId('crm_contact_id')
                    ->nullable()
                    ->constrained('crm_contacts')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('artists', 'crm_contact_id')) {
            Schema::table('artists', function (Blueprint $table): void {
                $table->foreignId('crm_contact_id')
                    ->nullable()
                    ->constrained('crm_contacts')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('manufacturers', 'crm_contact_id')) {
            Schema::table('manufacturers', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('crm_contact_id');
            });
        }

        if (Schema::hasColumn('accommodations', 'crm_contact_id')) {
            Schema::table('accommodations', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('crm_contact_id');
            });
        }

        if (Schema::hasColumn('artists', 'crm_contact_id')) {
            Schema::table('artists', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('crm_contact_id');
            });
        }
    }
};
