<?php

namespace Artwork\Modules\Holidays\Seeder;

use Artwork\Modules\Holidays\Models\Subdivision;

class SwissCantoneSeeder
{
    public function seed(): void
    {
        if (Subdivision::where('code', 'AG')->first()) {
            return;
        }

        foreach (
            [
                ['name' => 'Aargau', 'code' => 'AG', 'country_code' => 'CH'],
                ['name' => 'Appenzell Ausserrhoden', 'code' => 'AR', 'country_code' => 'CH'],
                ['name' => 'Appenzell Innerrhoden', 'code' => 'AI', 'country_code' => 'CH'],
                ['name' => 'Basel-Landschaft', 'code' => 'BL', 'country_code' => 'CH'],
                ['name' => 'Basel-Stadt', 'code' => 'BS', 'country_code' => 'CH'],
                ['name' => 'Bern', 'code' => 'BE', 'country_code' => 'CH'],
                ['name' => 'Freiburg', 'code' => 'FR', 'country_code' => 'CH'],
                ['name' => 'Genf', 'code' => 'GE', 'country_code' => 'CH'],
                ['name' => 'Glarus', 'code' => 'GL', 'country_code' => 'CH'],
                ['name' => 'Graubünden', 'code' => 'GR', 'country_code' => 'CH'],
                ['name' => 'Jura', 'code' => 'JU', 'country_code' => 'CH'],
                ['name' => 'Luzern', 'code' => 'LU', 'country_code' => 'CH'],
                ['name' => 'Neuenburg', 'code' => 'NE', 'country_code' => 'CH'],
                ['name' => 'Nidwalden', 'code' => 'NW', 'country_code' => 'CH'],
                ['name' => 'Obwalden', 'code' => 'OW', 'country_code' => 'CH'],
                ['name' => 'Schaffhausen', 'code' => 'SH', 'country_code' => 'CH'],
                ['name' => 'Schwyz', 'code' => 'SZ', 'country_code' => 'CH'],
                ['name' => 'Solothurn', 'code' => 'SO', 'country_code' => 'CH'],
                ['name' => 'St. Gallen', 'code' => 'SG', 'country_code' => 'CH'],
                ['name' => 'Tessin', 'code' => 'TI', 'country_code' => 'CH'],
                ['name' => 'Thurgau', 'code' => 'TG', 'country_code' => 'CH'],
                ['name' => 'Uri', 'code' => 'UR', 'country_code' => 'CH'],
                ['name' => 'Waadt', 'code' => 'VD', 'country_code' => 'CH'],
                ['name' => 'Wallis', 'code' => 'VS', 'country_code' => 'CH'],
                ['name' => 'Zug', 'code' => 'ZG', 'country_code' => 'CH'],
                ['name' => 'Zürich', 'code' => 'ZH', 'country_code' => 'CH'],
            ] as $cantoneData
        ) {
            Subdivision::create($cantoneData);
        }
    }
}
