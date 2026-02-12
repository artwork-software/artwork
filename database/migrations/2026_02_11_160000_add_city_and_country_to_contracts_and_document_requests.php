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
            $table->string('foreign_tax_city')->nullable()->after('foreign_tax_amount');
            $table->string('foreign_tax_country')->nullable()->after('foreign_tax_city');
        });

        Schema::table('document_requests', function (Blueprint $table) {
            $table->string('foreign_tax_city')->nullable()->after('foreign_tax_amount');
            $table->string('foreign_tax_country')->nullable()->after('foreign_tax_city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['foreign_tax_city', 'foreign_tax_country']);
        });

        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropColumn(['foreign_tax_city', 'foreign_tax_country']);
        });
    }
};
