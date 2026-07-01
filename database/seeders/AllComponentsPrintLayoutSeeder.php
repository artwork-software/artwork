<?php

namespace Database\Seeders;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ProjectPrintLayout;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * Creates a print layout that embeds ONE instance of every printable component
 * type (see ProjectTabComponentEnum::PRINTABLE) so the full print pipeline can
 * be verified end to end. Idempotent: re-running replaces the layout.
 */
class AllComponentsPrintLayoutSeeder extends Seeder
{
    public const LAYOUT_NAME = 'ALL COMPONENTS TEST';

    public function run(): void
    {
        $ownerId = User::query()->orderBy('id')->value('id');

        // Drop a previous run so the seeder stays idempotent.
        ProjectPrintLayout::where('name', self::LAYOUT_NAME)->each(function (ProjectPrintLayout $layout): void {
            $layout->components()->delete();
            $layout->delete();
        });

        $printable = ProjectTabComponentEnum::printableValues();

        // One component per printable type (first instance of each type).
        $componentsByType = Component::whereIn('type', $printable)
            ->orderBy('id')
            ->get()
            ->unique('type')
            ->values();

        $order = (int) ProjectPrintLayout::max('order') + 1;

        $layout = ProjectPrintLayout::create([
            'name' => self::LAYOUT_NAME,
            'description' => 'Auto-seeded layout containing every printable component (verification).',
            'is_default' => false,
            'columns_header' => 1,
            'columns_footer' => 1,
            'columns_body' => 1,
            'order' => $order,
            'is_active' => true,
            'user_id' => $ownerId,
            'permission' => 'allCanPrint',
            'notes' => ['header' => [], 'footer' => []],
        ]);

        // Header: the project title.
        $title = $componentsByType->firstWhere('type', ProjectTabComponentEnum::PROJECT_TITLE->value);
        if ($title) {
            $layout->components()->create([
                'component_id' => $title->id,
                'type' => 'header',
                'position' => 1,
                'row' => 1,
            ]);
        }

        // Body: every printable component, each on its own row.
        $row = 1;
        foreach ($componentsByType as $component) {
            $layout->components()->create([
                'component_id' => $component->id,
                'type' => 'body',
                'position' => 1,
                'row' => $row++,
            ]);
        }

        // Bust the palette caches used by the settings page.
        Cache::forget('print_layout_components_not_special_v2');
        Cache::forget('print_layout_components_special_v2');
        Cache::forget('print_layout_all_components_v2');

        $this->command?->info(sprintf(
            'Seeded print layout "%s" (id %d) with %d body components.',
            self::LAYOUT_NAME,
            $layout->id,
            $componentsByType->count()
        ));
    }
}
