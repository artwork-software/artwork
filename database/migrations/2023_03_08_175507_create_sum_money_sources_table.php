<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sum_money_sources', function (Blueprint $table) {
            $table->id();
            $table->morphs('sourceable');
            $table->bigInteger('money_source_id')->nullable();
            $table->string('linked_type')->nullable();
            $table->timestamps();
        });
    }
};
