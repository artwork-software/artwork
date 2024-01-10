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
        Schema::create('vacations', static function (Blueprint $t): void {
            $t->id();
            $t->morphs('vacationer');
            $t->time('start_time');
            $t->time('end_time');
            $t->date('date');
            $t->boolean('full_day')->default(false);
            $t->string('comment', 20)->nullable();
            $t->boolean('is_serie')->default(false);
            $t->integer('serie_id')->nullable();
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        //
    }
};
