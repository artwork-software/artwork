<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeEventTypeSvgToHexSeed extends Seeder
{
    //phpcs:ignore
    public function run(): void
    {
        foreach (DB::table('event_types')->get() as $eventType) {
            if (
                empty($eventType->hex_code) &&
                property_exists($eventType, $eventType->svg_name) &&
                !empty($eventType->svg_name)
            ) {
                DB::table('event_types')
                    ->where('id', $eventType->id)
                    ->update(
                        [
                            'hex_code' => match ($eventType->svg_name) {
                                'eventType0' => '#A7A6B1',
                                'eventType1' => '#641A54',
                                'eventType2' => '#DA3F87',
                                'eventType3' => '#EB7A3D',
                                'eventType4' => '#F1B640',
                                'eventType5' => '#86C554',
                                'eventType6' => '#2EAA63',
                                'eventType7' => '#3DC3CB',
                                'eventType8' => '#168FC3',
                                'eventType9' => '#4D908E',
                                'eventType10' => '#21485C',
                            }
                        ]
                    );
            }
        }

        if (Schema::hasColumn('event_types', 'svg_name')) {
            Schema::table('event_types', function ($table): void {
                $table->dropColumn('svg_name');
            });
        }
    }
}
