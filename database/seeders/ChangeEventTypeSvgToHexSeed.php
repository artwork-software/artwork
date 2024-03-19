<?php

namespace Database\Seeders;

use DOMDocument;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeEventTypeSvgToHexSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->changeSvgToHex();

        // Überprüfen, ob die Spalte existiert, bevor sie gelöscht wird
        if (Schema::hasColumn('event_types', 'svg_name')) {
            Schema::table('event_types', function ($table): void {
                $table->dropColumn('svg_name');
            });
        }
    }


    private function changeSvgToHex(): void
    {
        $eventTypes = DB::table('event_types')->get();
        foreach ($eventTypes as $eventType) {
            // Überprüfen, ob svg_name vorhanden ist, andernfalls einen leeren String verwenden
            $svgName = property_exists($eventType, 'svg_name') ? $eventType->svg_name : '';
            $hex = $this->getHexFromSvg($svgName);
            DB::table('event_types')
                ->where('id', $eventType->id)
                ->update(['hex_code' => $hex]);
        }
    }

    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    private function getHexFromSvg(string $svg): string
    {
        // Wenn $svg leer ist, frühzeitig zurückkehren
        if (empty($svg)) {
            return '';
        }
        $svgName = '';
        switch ($svg) {
            case 'eventType0':
                $svgName = 'eventType_color_00';
                break;
            case 'eventType1':
                $svgName = 'eventType_color_01';
                break;
            case 'eventType2':
                $svgName = 'eventType_color_02';
                break;
            case 'eventType3':
                $svgName = 'eventType_color_03';
                break;
            case 'eventType4':
                $svgName = 'eventType_color_04';
                break;
            case 'eventType5':
                $svgName = 'eventType_color_05';
                break;
            case 'eventType6':
                $svgName = 'eventType_color_06';
                break;
            case 'eventType7':
                $svgName = 'eventType_color_07';
                break;
            case 'eventType8':
                $svgName = 'eventType_color_08';
                break;
            case 'eventType9':
                $svgName = 'eventType_color_09';
                break;
            case 'eventType10':
                $svgName = 'eventType_color_10';
                break;
        }
        // first get the svg file from storage
        $path = public_path('Svgs/eventTypeSvgs/' . $svgName . '.svg');
        $file = file_get_contents($path);
        $dom = new DOMDocument();
        $dom->loadXML($file);

        // check if the svg is a circle or ellipse
        $circle = $dom->getElementsByTagName('circle');
        $ellipse = $dom->getElementsByTagName('ellipse');
        if ($circle->length > 0) {
            return $this->getHexFromCircle($circle);
        } elseif ($ellipse->length > 0) {
            return $this->getHexFromEllipse($ellipse);
        }
        return '';
    }

    private function getHexFromCircle($circle): string
    {
        $hex = '';
        foreach ($circle as $c) {
            $hex = $c->getAttribute('fill');
        }
        return $hex;
    }

    private function getHexFromEllipse($ellipse): string
    {
        $hex = '';
        foreach ($ellipse as $e) {
            $hex = $e->getAttribute('fill');
        }
        return $hex;
    }
}
