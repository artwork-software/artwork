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
        Schema::create('freelancer_shift_qualifications', function (Blueprint $table): void {
            $table->unsignedbigInteger('freelancer_id');
            $table->foreign('freelancer_id')
                ->references('id')
                ->on('freelancers')
                ->cascadeOnDelete();
            $table->unsignedbigInteger('shift_qualification_id');
            $table->foreign('shift_qualification_id')
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
        Schema::dropIfExists('freelancer_shift_qualifications');
    }
};
