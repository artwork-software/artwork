<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\BusinessIntelligence\Models\BiEventTypeTag;
use Illuminate\Console\Command;

class AddBiEventTypeTagsCommand extends Command
{
    protected $signature = 'artwork:add-bi-event-type-tags';
    protected $description = 'Seed the default set of Business Intelligence event-type tags (idempotent)';

    /**
     * @var array<int, array{name: string, name_de: string, color: string}>
     */
    private const DEFAULT_TAGS = [
        ['name' => 'Event day', 'name_de' => 'Veranstaltungstag', 'color' => '#6366f1'],
        ['name' => 'Performance', 'name_de' => 'Vorstellung', 'color' => '#22c55e'],
        ['name' => 'Rehearsal', 'name_de' => 'Probe', 'color' => '#f59e0b'],
        ['name' => 'Education', 'name_de' => 'Vermittlung', 'color' => '#ec4899'],
        ['name' => 'Special event', 'name_de' => 'Sonderveranstaltung', 'color' => '#06b6d4'],
    ];

    public function handle(): void
    {
        foreach (self::DEFAULT_TAGS as $tag) {
            $existing = BiEventTypeTag::query()
                ->where('name', $tag['name'])
                ->orWhere('name_de', $tag['name_de'])
                ->first();

            if ($existing) {
                $this->info('BI event type tag "' . $tag['name_de'] . '" already exists');
                continue;
            }

            BiEventTypeTag::create($tag);
            $this->info('BI event type tag "' . $tag['name_de'] . '" added');
        }
    }
}
