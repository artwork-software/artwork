<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('color', 7)->default('#000000');
            $table->boolean('has_restricted_permissions')->default(false);
            //['all_can_edit', 'restricted_edit']
            $table->string('permission_mode')->default('restricted_edit');
            $table->foreignId('inventory_tag_group_id')
                ->nullable()
                ->constrained('inventory_tag_groups')
                ->nullOnDelete();

            $table->unsignedInteger('position')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_tags');
    }
};
