<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artist_residencies', function (Blueprint $table): void {
            $table->foreignId('artist_crm_contact_id')
                ->nullable()
                ->after('artist_id')
                ->constrained('crm_contacts')
                ->nullOnDelete();
            $table->foreignId('accommodation_crm_contact_id')
                ->nullable()
                ->after('accommodation_id')
                ->constrained('crm_contacts')
                ->nullOnDelete();
        });

        Schema::table('accommodation_accommodation_room_type', function (Blueprint $table): void {
            $table->foreignId('crm_contact_id')
                ->nullable()
                ->after('accommodation_id')
                ->constrained('crm_contacts')
                ->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('crm_contact_id')
                ->nullable()
                ->constrained('crm_contacts')
                ->nullOnDelete();
        });

        Schema::table('freelancers', function (Blueprint $table): void {
            $table->foreignId('crm_contact_id')
                ->nullable()
                ->constrained('crm_contacts')
                ->nullOnDelete();
        });

        Schema::table('service_providers', function (Blueprint $table): void {
            $table->foreignId('crm_contact_id')
                ->nullable()
                ->constrained('crm_contacts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('artist_residencies', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('artist_crm_contact_id');
            $table->dropConstrainedForeignId('accommodation_crm_contact_id');
        });

        Schema::table('accommodation_accommodation_room_type', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('crm_contact_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('crm_contact_id');
        });

        Schema::table('freelancers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('crm_contact_id');
        });

        Schema::table('service_providers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('crm_contact_id');
        });
    }
};
