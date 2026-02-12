<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('contract_state')->nullable()->after('description');
            $table->text('contract_state_comment')->nullable()->after('contract_state');
        });

        Schema::table('document_requests', function (Blueprint $table) {
            $table->string('contract_state')->nullable()->after('comment');
            $table->text('contract_state_comment')->nullable()->after('contract_state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['contract_state', 'contract_state_comment']);
        });

        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropColumn(['contract_state', 'contract_state_comment']);
        });
    }
};
