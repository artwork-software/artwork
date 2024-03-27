<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('service_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('profile_image')->nullable();
            $table->string('provider_name')->default('Neuer Dienstleister');
            $table->string('work_name')->nullable();
            $table->string('work_description')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('street')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('location')->nullable();
            $table->string('note', 500)->nullable();
            $table->integer('salary_per_hour')->nullable();
            $table->longText('salary_description')->nullable();
            $table->boolean('can_master')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('service_providers');
    }
};
