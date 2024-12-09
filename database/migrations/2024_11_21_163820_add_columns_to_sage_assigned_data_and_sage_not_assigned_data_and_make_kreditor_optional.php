<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'sage_assigned_data',
            function (Blueprint $table) {
                $table->unsignedBigInteger('periode')->after('tan');
                $table->string('kto_haben')->after('periode');
                $table->string('kto_soll')->after('belegdatum');
                $table->decimal('buchungsbetrag', 12)->change();
            }
        );

        Schema::table(
            'sage_not_assigned_data',
            function (Blueprint $table) {
                $table->unsignedBigInteger('periode')->after('tan');
                $table->string('kto_haben')->after('periode');
                $table->string('kto_soll')->after('belegdatum');
                $table->decimal('buchungsbetrag', 12)->change();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'sage_assigned_data',
            function (Blueprint $table) {
                $table->dropColumn('periode');
                $table->dropColumn('kto_haben');
                $table->dropColumn('kto_soll');
                $table->decimal('buchungsbetrag', 8)->change();
            }
        );

        Schema::table(
            'sage_not_assigned_data',
            function (Blueprint $table) {
                $table->dropColumn('periode');
                $table->dropColumn('kto_haben');
                $table->dropColumn('kto_soll');
                $table->decimal('buchungsbetrag', 8)->change();
            }
        );
    }
};
