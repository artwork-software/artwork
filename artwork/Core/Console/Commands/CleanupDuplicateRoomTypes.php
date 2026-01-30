<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Accommodation\Models\AccommodationRoomType;
use Artwork\Modules\ArtistResidency\Enums\TypOfRoom;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupDuplicateRoomTypes extends Command
{
    protected $signature = 'artwork:cleanup-duplicate-room-types';
    protected $description = 'Removes duplicate AccommodationRoomType entries and reassigns relations';

    public function handle(): void
    {
        $this->info('--- Checking for duplicate room types ---');

        // Prüfen ob es überhaupt Duplikate gibt
        $hasDuplicates = false;
        foreach (TypOfRoom::cases() as $roomType) {
            $count = AccommodationRoomType::where('name', $roomType->value)->count();
            if ($count > 1) {
                $hasDuplicates = true;
                break;
            }
        }

        if (!$hasDuplicates) {
            $this->info('No duplicate room types found. Nothing to do.');
            return;
        }

        $this->info('Duplicates found. Starting cleanup...');

        DB::transaction(function () {
            foreach (TypOfRoom::cases() as $roomType) {
                $roomTypeName = $roomType->value;
                $duplicates = AccommodationRoomType::where('name', $roomTypeName)
                    ->orderBy('id')
                    ->get();

                if ($duplicates->count() <= 1) {
                    continue;
                }

                $this->info("Processing: {$roomTypeName} ({$duplicates->count()} entries)");

                // Der erste Eintrag bleibt erhalten
                $keepRoomType = $duplicates->first();
                $duplicatesToDelete = $duplicates->skip(1);

                foreach ($duplicatesToDelete as $duplicate) {
                    // Alle Zuweisungen auf den zu behaltenden Raumtyp umleiten
                    DB::table('accommodation_accommodation_room_type')
                        ->where('accommodation_room_type_id', $duplicate->id)
                        ->update(['accommodation_room_type_id' => $keepRoomType->id]);

                    // Duplikat löschen
                    $duplicate->delete();
                    $this->info("  - Deleted duplicate ID: {$duplicate->id}, reassigned to ID: {$keepRoomType->id}");
                }
            }
        });

        $this->info('--- Cleanup finished ---');
    }
}
