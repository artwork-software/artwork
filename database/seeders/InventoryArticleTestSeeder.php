<?php

namespace Database\Seeders;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventoryArticleStatus;
use Illuminate\Database\Seeder;

class InventoryArticleTestSeeder extends Seeder
{
    public function run(): void
    {
        $categories = InventoryCategory::with('subCategories')->get();

        if ($categories->isEmpty()) {
            $this->command->warn('Keine InventoryCategories vorhanden – Seeder übersprungen.');
            return;
        }

        $statusIds = InventoryArticleStatus::pluck('id')->toArray();

        $articleTemplates = [
            // Licht / Beleuchtung
            ['name' => 'LED PAR 64 RGBW', 'description' => 'LED-Scheinwerfer PAR64, 4-Kanal RGBW, 120W', 'quantity' => 24],
            ['name' => 'Moving Head Spot 250W', 'description' => 'Moving Head mit Spot-Optik, DMX-steuerbar', 'quantity' => 8],
            ['name' => 'Followspot 1200W', 'description' => 'Verfolger-Scheinwerfer mit Irisblende', 'quantity' => 2],
            ['name' => 'LED Fluter 500W', 'description' => 'Asymmetrischer LED-Fluter für Flächen', 'quantity' => 12],
            ['name' => 'Stufenlinse 1kW', 'description' => 'Stufenlinsen-Scheinwerfer, Tungsten', 'quantity' => 16],
            ['name' => 'DMX-Splitter 8-fach', 'description' => 'Optisch isolierter DMX-Splitter', 'quantity' => 6],
            ['name' => 'Lichtpult GrandMA 2', 'description' => 'Lichtsteuerpult mit 2 Screens', 'quantity' => 1],
            ['name' => 'LED Strip 5m RGBW', 'description' => 'LED-Streifen, 60 LEDs/m, IP65', 'quantity' => 30],

            // Ton / Audio
            ['name' => 'Aktivbox 12" 1000W', 'description' => 'Aktive Fullrange-Box, Bi-Amped', 'quantity' => 8],
            ['name' => 'Subwoofer 18" 1200W', 'description' => 'Aktiver Subwoofer, Cardioid-Modus', 'quantity' => 4],
            ['name' => 'Funkmikrofon Handheld', 'description' => 'UHF-Funkmikrofon mit Handsender', 'quantity' => 12],
            ['name' => 'Kondensatormikrofon Großmembran', 'description' => 'Studiomikrofon mit Spinne und Windschutz', 'quantity' => 4],
            ['name' => 'Mischpult 32-Kanal Digital', 'description' => 'Digitalmischpult, 32 Inputs, Dante', 'quantity' => 2],
            ['name' => 'Stagebox 32/16', 'description' => 'Digitale Stagebox, 32 In / 16 Out', 'quantity' => 3],
            ['name' => 'In-Ear Monitoring Set', 'description' => 'Stereo In-Ear Set mit Bodypack', 'quantity' => 8],
            ['name' => 'DI-Box aktiv', 'description' => 'Aktive DI-Box, 2-Kanal', 'quantity' => 10],

            // Video / Projektion
            ['name' => 'Beamer 10.000 ANSI', 'description' => 'Laserprojektor, WUXGA, Lens-Shift', 'quantity' => 3],
            ['name' => 'LED-Wand Panel 50x50cm', 'description' => 'Indoor LED-Panel, P2.5, 160x160 Pixel', 'quantity' => 40],
            ['name' => 'Video-Mischer 4K', 'description' => '4-Kanal Video-Mischer mit Streaming-Ausgang', 'quantity' => 1],
            ['name' => 'HDMI-Kabel 10m', 'description' => 'HDMI 2.0 Kabel, vergoldet, 10 Meter', 'quantity' => 15],
            ['name' => 'SDI-Kabel 20m', 'description' => '3G-SDI Kabel, BNC, 20 Meter', 'quantity' => 10],

            // Rigging / Traverse
            ['name' => 'Traverse 2m Alu', 'description' => 'Alu-Traverse, Vierpunkt, 290mm', 'quantity' => 20],
            ['name' => 'Traverse 3m Alu', 'description' => 'Alu-Traverse, Vierpunkt, 290mm', 'quantity' => 16],
            ['name' => 'Kettenzug 500kg', 'description' => 'Elektro-Kettenzug, 500kg, 10m Kette', 'quantity' => 8],
            ['name' => 'Rundschlinge 2t 3m', 'description' => 'Textile Rundschlinge, WLL 2t', 'quantity' => 20],
            ['name' => 'Schäkel 1t', 'description' => 'Bügelschäkel, verzinkt, WLL 1t', 'quantity' => 30],
            ['name' => 'Stativ Boxen/Licht', 'description' => 'Aluminium-Stativ, max. 50kg, 3.5m', 'quantity' => 12],

            // Bühne / Podeste
            ['name' => 'Podest 2x1m', 'description' => 'Bühnenpodest, Aluminium, klappbar', 'quantity' => 24],
            ['name' => 'Podestfuß 40cm', 'description' => 'Teleskop-Podestfuß, 20-40cm', 'quantity' => 48],
            ['name' => 'Podestfuß 80cm', 'description' => 'Teleskop-Podestfuß, 40-80cm', 'quantity' => 48],
            ['name' => 'Bühnentreppe 3-stufig', 'description' => 'Treppe für Podesthöhe 60cm', 'quantity' => 4],
            ['name' => 'Bühnenmolton schwarz 6x3m', 'description' => 'Molton-Vorhang, schwarz, B1', 'quantity' => 10],

            // Kabel / Strom
            ['name' => 'CEE 16A Kabel 25m', 'description' => 'CEE-Verlängerung, 3-phasig, 16A', 'quantity' => 10],
            ['name' => 'CEE 32A Kabel 25m', 'description' => 'CEE-Verlängerung, 3-phasig, 32A', 'quantity' => 6],
            ['name' => 'Schuko-Verteiler 6-fach', 'description' => 'Steckdosenleiste mit Überspannungsschutz', 'quantity' => 20],
            ['name' => 'XLR-Kabel 10m', 'description' => '3-pol XLR-Kabel, Neutrik', 'quantity' => 40],
            ['name' => 'XLR-Kabel 20m', 'description' => '3-pol XLR-Kabel, Neutrik', 'quantity' => 20],
            ['name' => 'Multicore 16/4 30m', 'description' => 'Multicore-Kabel, 16 Sends / 4 Returns', 'quantity' => 3],
            ['name' => 'Powercon-Kabel 5m', 'description' => 'Powercon TRUE1 Verbindungskabel', 'quantity' => 20],
            ['name' => 'Cat6 Netzwerkkabel 30m', 'description' => 'Cat6a Ethercon-Kabel, geschirmt', 'quantity' => 10],

            // Werkzeug / Sonstiges
            ['name' => 'Akkuschrauber Set', 'description' => '18V Akkuschrauber mit 2 Akkus', 'quantity' => 4],
            ['name' => 'Gaffa-Tape schwarz 50m', 'description' => 'Gewebeklebeband, 50mm breit', 'quantity' => 50],
            ['name' => 'Kabelbinder Set 100 Stk.', 'description' => 'Kabelbinder, diverse Größen', 'quantity' => 20],
            ['name' => 'Sicherungsseil 100cm', 'description' => 'Stahlseil mit Karabiner, 50kg', 'quantity' => 30],
            ['name' => 'Zange Seitenschneider', 'description' => 'Seitenschneider, isoliert', 'quantity' => 6],
            ['name' => 'Maßband 5m', 'description' => 'Rollmaßband mit Magnetspitze', 'quantity' => 8],

            // Möbel / Ausstattung
            ['name' => 'Klappstuhl schwarz', 'description' => 'Klappstuhl, Metall, gepolstert', 'quantity' => 100],
            ['name' => 'Klapptisch 180x75cm', 'description' => 'Bankett-Klapptisch, weiß', 'quantity' => 20],
            ['name' => 'Stehtisch rund Ø80cm', 'description' => 'Stehtisch mit Husse, weiß', 'quantity' => 10],
            ['name' => 'Garderobenstange mobil', 'description' => 'Rollbare Garderobenstange, 150cm', 'quantity' => 6],
        ];

        $created = 0;

        foreach ($articleTemplates as $template) {
            $category = $categories->random();
            $subCategory = $category->subCategories->isNotEmpty()
                ? $category->subCategories->random()
                : null;

            if (!$subCategory) {
                continue;
            }

            $article = InventoryArticle::create([
                'name' => $template['name'],
                'description' => $template['description'],
                'quantity' => $template['quantity'],
                'inventory_category_id' => $category->id,
                'inventory_sub_category_id' => $subCategory->id,
                'is_detailed_quantity' => false,
            ]);

            // Attach random status values if statuses exist
            if (!empty($statusIds)) {
                foreach ($statusIds as $statusId) {
                    $article->statusValues()->attach($statusId, [
                        'value' => rand(0, $template['quantity']),
                    ]);
                }
            }

            $created++;
            $this->command->info("  Artikel: {$article->name} (Kat: {$category->name} / {$subCategory->name})");
        }

        $this->command->info("InventoryArticleTestSeeder fertig: {$created} Artikel erstellt.");
    }
}
