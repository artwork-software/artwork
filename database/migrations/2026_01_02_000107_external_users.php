<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('external_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('identification');
            $table->json('meta_data')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('external_user_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->string('type'); //ldap or identity_provider
            $table->json('config')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_users');
        Schema::dropIfExists('external_user_sources');
    }
};
