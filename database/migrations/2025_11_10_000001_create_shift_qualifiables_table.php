<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_qualifiables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shift_qualification_id');
            $table->unsignedBigInteger('qualifiable_id');
            $table->string('qualifiable_type');
            $table->unsignedBigInteger('craft_id')->nullable();
            $table->timestamps();

            $table->unique([
                'shift_qualification_id',
                'qualifiable_id',
                'qualifiable_type',
                'craft_id'
            ], 'shift_qualifiable_unique');

            $table->foreign('shift_qualification_id')
                ->references('id')->on('shift_qualifications')->onDelete('cascade');
            $table->foreign('craft_id')
                ->references('id')->on('crafts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_qualifiables');
    }
};

