<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artist_residencies', function (Blueprint $table) {
            $table->boolean('do_not_save_artist')->default(false)->after('description');
            $table->string('name')->nullable()->after('do_not_save_artist');
            $table->string('civil_name')->nullable()->after('name');
            $table->string('phone_number')->nullable()->after('civil_name');
            $table->string('position')->nullable()->after('phone_number');
        });
    }

    public function down(): void
    {
        Schema::table('artist_residencies', function (Blueprint $table) {
            $table->dropColumn(['do_not_save_artist', 'name', 'civil_name', 'phone_number', 'position']);
        });
    }
};
