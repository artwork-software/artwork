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
        Schema::create('shift_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade');
            $table->string('employable_type');
            $table->unsignedBigInteger('employable_id');
            $table->foreignId('shift_qualification_id')->constrained('shift_qualifications');
            $table->unsignedBigInteger('shift_count')->default(1);
            $table->string('craft_abbreviation')->nullable();
            $table->string('short_description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employable_type', 'employable_id']);
            $table->index('shift_id');
            $table->index('shift_qualification_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_workers');
    }
};
