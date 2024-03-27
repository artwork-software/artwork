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
        Schema::create('service_provider_shift_qualifications', function (Blueprint $table): void {
            $table->unsignedbigInteger('service_provider_id');
            $table->foreign('service_provider_id', 'service_provider_id_foreign')
                ->references('id')
                ->on('service_providers')
                ->cascadeOnDelete();
            $table->unsignedbigInteger('shift_qualification_id');
            $table->foreign('shift_qualification_id', 'service_provider_id_shift_qualification_foreign')
                ->references('id')
                ->on('shift_qualifications')
                ->cascadeOnDelete();
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
        Schema::dropIfExists('service_provider_shift_qualifications');
    }
};
