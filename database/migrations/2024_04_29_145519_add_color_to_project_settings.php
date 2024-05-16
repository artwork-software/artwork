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
        Schema::table('sectors', function (Blueprint $table) {
            $table->string('color')->nullable()->after('name');
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->string('color')->nullable()->after('name');
        });
        Schema::table('genres', function (Blueprint $table) {
            $table->string('color')->nullable()->after('name');
        });
        Schema::table('contract_types', function (Blueprint $table) {
            $table->string('color')->nullable()->after('name');
        });
        Schema::table('company_types', function (Blueprint $table) {
            $table->string('color')->nullable()->after('name');
        });
        Schema::table('collecting_societies', function (Blueprint $table) {
            $table->string('color')->nullable()->after('name');
        });
        Schema::table('currencies', function (Blueprint $table) {
            $table->string('color')->nullable()->after('name');
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sectors', function (Blueprint $table) {
            $table->dropColumn('color');
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('color');
        });
        Schema::table('genres', function (Blueprint $table) {
            $table->dropColumn('color');
        });
        Schema::table('contract_types', function (Blueprint $table) {
            $table->dropColumn('color');
        });
        Schema::table('company_types', function (Blueprint $table) {
            $table->dropColumn('color');
        });
        Schema::table('collecting_societies', function (Blueprint $table) {
            $table->dropColumn('color');
        });
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
