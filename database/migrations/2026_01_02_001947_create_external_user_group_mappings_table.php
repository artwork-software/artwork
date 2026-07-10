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
        Schema::create('external_user_group_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_id');
            $table->string('ad_group_dn'); // Distinguished Name der AD-Gruppe
            $table->string('ad_group_name'); // Name der AD-Gruppe (z.B. CN=Artwork_Users)
            $table->json('permission_ids')->nullable(); // Array von Permission-IDs
            $table->json('role_ids')->nullable(); // Array von Role-IDs
            $table->boolean('include_nested_groups')->default(true); // Verschachtelte Gruppen unterstützen
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_user_group_mappings');
    }
};

