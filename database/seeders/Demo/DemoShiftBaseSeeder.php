<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Models\ShiftGroup;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftTimePreset;
use Database\Seeders\Demo\Support\DemoDataPools;
use Illuminate\Database\Seeder;

/**
 * Schicht-Basisdaten: Gewerke (inkl. universeller Gewerke), Funktionen,
 * Zeit-Presets und Schichtgruppen. Idempotent über Namen.
 */
class DemoShiftBaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedQualifications();
        $this->seedCrafts();
        $this->seedTimePresets();
        $this->seedShiftGroups();
    }

    private function seedQualifications(): void
    {
        $created = 0;
        foreach (DemoDataPools::QUALIFICATIONS as $data) {
            $qualification = ShiftQualification::firstOrCreate(
                ['name' => $data['name']],
                ['icon' => $data['icon'], 'available' => true, 'position' => $data['position']]
            );
            if ($qualification->wasRecentlyCreated) {
                $created++;
            }
        }
        $this->command?->info(sprintf('Funktionen: %d neu, %d vorhanden.', $created, count(DemoDataPools::QUALIFICATIONS) - $created));
    }

    private function seedCrafts(): void
    {
        $created = 0;

        foreach (DemoDataPools::CRAFTS as $data) {
            $craft = Craft::firstOrCreate(
                ['name' => $data['name']],
                [
                    'abbreviation' => $data['abbreviation'],
                    'color' => $data['color'],
                    'position' => $data['position'],
                    'assignable_by_all' => false,
                    'universally_applicable' => $data['universally_applicable'] ?? false,
                ]
            );
            if ($craft->wasRecentlyCreated) {
                $created++;
            }
        }

        // JEDE Funktion an JEDES Gewerk (auch Bestandsgewerke): Schichtbedarfe
        // mit einer dem Gewerk fremden Funktion wären im UI nicht besetzbar.
        $allQualificationIds = ShiftQualification::query()->pluck('id')->all();
        foreach (Craft::all() as $craft) {
            $craft->qualifications()->syncWithoutDetaching($allQualificationIds);
        }

        $this->command?->info(sprintf(
            'Gewerke: %d neu angelegt; alle %d Funktionen an alle Gewerke zugeordnet.',
            $created,
            count($allQualificationIds)
        ));
    }

    private function seedTimePresets(): void
    {
        foreach (DemoDataPools::SHIFT_TIME_PRESETS as $preset) {
            ShiftTimePreset::firstOrCreate(['name' => $preset['name']], $preset);
        }
        $this->command?->info('Schicht-Zeit-Presets angelegt.');
    }

    private function seedShiftGroups(): void
    {
        foreach (DemoDataPools::SHIFT_GROUPS as $group) {
            ShiftGroup::firstOrCreate(['name' => $group['name']], $group);
        }
        $this->command?->info('Schichtgruppen angelegt.');
    }
}
