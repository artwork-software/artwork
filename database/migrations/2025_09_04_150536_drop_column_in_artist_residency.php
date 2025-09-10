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
        Schema::table('artist_residencies', function (Blueprint $table) {

            if(!Schema::hasColumn('artist_residencies', 'artist_id')) {
                $table->unsignedBigInteger('artist_id')->nullable()->after('id');

                // add foreign key constraint
                $table->foreign('artist_id', 'fk_artist_residencies_artist')
                    ->references('id')->on('artists')
                    ->onDelete('set null');
            }

            // drop name, civil_name, phone_number, position
            $table->dropColumn('name');
            $table->dropColumn('civil_name');
            $table->dropColumn('phone_number');
            $table->dropColumn('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('artist_residencies', function (Blueprint $table) {
            //
        });
    }
};
