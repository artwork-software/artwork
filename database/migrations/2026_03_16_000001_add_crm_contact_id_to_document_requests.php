<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('document_requests', 'crm_contact_id')) {
            Schema::table('document_requests', function (Blueprint $table) {
                $table->foreignId('crm_contact_id')->nullable()->after('contract_partner')
                    ->constrained('crm_contacts')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('document_requests', 'crm_contact_id')) {
            Schema::table('document_requests', function (Blueprint $table) {
                $table->dropConstrainedForeignId('crm_contact_id');
            });
        }
    }
};
