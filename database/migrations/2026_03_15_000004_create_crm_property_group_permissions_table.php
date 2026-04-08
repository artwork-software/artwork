<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_property_group_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_property_group_id')->constrained('crm_property_groups')->cascadeOnDelete();
            $table->string('permissionable_type');
            $table->unsignedBigInteger('permissionable_id');
            $table->index(['permissionable_type', 'permissionable_id'], 'crm_pgp_permissionable_index');
            $table->boolean('can_view')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_property_group_permissions');
    }
};
