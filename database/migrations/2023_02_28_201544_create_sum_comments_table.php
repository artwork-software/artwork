<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sum_comments', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable');
            $table->text("comment");
            $table->unsignedBigInteger("user_id");
            $table->timestamps();
        });
    }

};
