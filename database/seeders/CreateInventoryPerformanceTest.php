<?php

namespace Database\Seeders;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryManagement\Models\CraftsInventoryColumn;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CreateInventoryPerformanceTest extends Seeder
{
    /**
     * Run the database seeds.
     */
    //phpcs:ignore Generic.Metrics.NestingLevel.TooHigh
    public function run(): void
    {
        $crafts = Craft::all();

        $crafts->each(function (Craft $craft): void {
            $faker = \Faker\Factory::create();

            for ($i = 0; $i < 10; $i++) {
                $category = $craft->inventoryCategories()->create([
                    'name' => $faker->name,
                    'order' => $i,
                ]);

                for ($j = 0; $j < 10; $j++) {
                    $group = $category->groups()->create([
                        'name' => $faker->name,
                        'order' => $j,
                    ]);

                    for ($k = 0; $k < 5; $k++) {
                        $item = $group->items()->create([
                            'order' => $k,
                        ]);

                        $columns = CraftsInventoryColumn::all();

                        foreach ($columns as $column) {
                            if ($column->name === 'Name') {
                                $item->cells()->create([
                                    'crafts_inventory_column_id' => $column->id,
                                    'cell_value' => $faker->realText(20),
                                ]);
                            } elseif ($column->name === 'Anzahl') {
                                $item->cells()->create([
                                    'crafts_inventory_column_id' => $column->id,
                                    'cell_value' => $faker->numberBetween(1, 100),
                                ]);
                            } elseif ($column->name === 'Kommentar') {
                                $item->cells()->create([
                                    'crafts_inventory_column_id' => $column->id,
                                    'cell_value' => $faker->realText(20),
                                ]);
                            }
                        }
                    }
                }
            }
        });

        $this->call(BenchmarkProjectSeeder::class);

        // get all Events
        $events = Event::all();

        // get all Inventory Items
        $items = CraftInventoryItem::all();

        // assign random inventory items to random events
        $events->each(function (Event $event) use ($items): void {
            $items->random(20)->each(function (CraftInventoryItem $item) use ($event): void {
                $this->command->info('Assigning Item ' . $item->id . ' to Event ' . $event->id);
                $item->events()->create([
                    'event_id' => $event->id,
                    'quantity' => random_int(1, 10),
                    'comment' => 'Comment',
                    'start' => $event->start_time,
                    'end' => $event->end_time,
                    'is_all_day' => $event->allDay,
                    'user_id' => $event->user_id,
                ]);
            });
        });
    }
}
