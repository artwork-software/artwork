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
        Schema::table(
            'sage_assigned_data',
            function (Blueprint $table) {
                $table->double('periode')->after('tan');
                $table->double('kto_haben')->after('periode');
                $table->double('kto_soll')->after('belegdatum');
            }
        );

        Schema::table(
            'sage_not_assigned_data',
            function (Blueprint $table) {
                $table->double('periode')->after('tan');
                $table->double('kto_haben')->after('periode');
                $table->double('kto_soll')->after('belegdatum');
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(
            'sage_assigned_data',
            function (Blueprint $table) {
                $table->float('periode')->after('tan');
                $table->float('kto_haben')->after('periode');
                $table->float('kto_soll')->after('belegdatum');
            }
        );

        Schema::table(
            'sage_not_assigned_data',
            function (Blueprint $table) {
                $table->float('periode')->after('tan');
                $table->float('kto_haben')->after('periode');
                $table->float('kto_soll')->after('belegdatum');
            }
        );
    }
};
