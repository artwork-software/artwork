<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('freelancers', function (Blueprint $table) {
            $table->string('first_name')->nullable()->default(null)->change();
            $table->string('last_name')->nullable()->default(null)->change();
        });

        Schema::table('service_providers', function (Blueprint $table) {
            $table->string('provider_name')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('freelancers', function (Blueprint $table) {
            $table->string('first_name')->default('Neuer')->change();
            $table->string('last_name')->default('Freelancer')->change();
        });

        Schema::table('service_providers', function (Blueprint $table) {
            $table->string('provider_name')->default('Neuer Dienstleister')->change();
        });
    }
};
