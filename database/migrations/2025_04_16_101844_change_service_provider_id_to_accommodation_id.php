<?php

use Artwork\Modules\Accommodation\Models\Accommodation;
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
        // create Fallback accommodation
        $fallbackAccommodation = Accommodation::create();

        Schema::table('artist_residencies', function (Blueprint $table) {
            $table->foreignId('accommodation_id')
                ->after('service_provider_id')
                ->nullable()
                ->constrained('accommodations')
                ->nullOnDelete()
                ->cascadeOnUpdate();



            $table->dropForeign(['service_provider_id']);
            $table->dropColumn('service_provider_id');

        });

        // update all existing artist residencies to use the fallback accommodation
        $artistResidencies = \Artwork\Modules\ArtistResidency\Models\ArtistResidency::all();
        foreach ($artistResidencies as $artistResidency) {
            $artistResidency->update(['accommodation_id' => $fallbackAccommodation->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('artist_residencies', function (Blueprint $table) {
            $table->foreignId('service_provider_id')
                ->after('accommodation_id')
                ->nullable()
                ->constrained('service_providers')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->dropForeign(['accommodation_id']);
            $table->dropColumn('accommodation_id');
        });
    }
};
