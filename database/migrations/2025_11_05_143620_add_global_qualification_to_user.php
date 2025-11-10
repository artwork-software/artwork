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
        Schema::create('global_qualifiables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('global_qualification_id');
            $table->unsignedBigInteger('qualifiable_id');
            $table->string('qualifiable_type');
            $table->timestamps();

            $table->unique([
                'global_qualification_id',
                'qualifiable_id',
                'qualifiable_type'
            ], 'gq_qualifiable_unique');

            $table->foreign('global_qualification_id', 'fk_gq_qualification')
                ->references('id')->on('global_qualifications')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_qualifiables');
    }
};
