<?php

namespace Tests\Feature\Http\Controllers\Project;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Der Projekt-Schichten-Tab lud bei jedem Aufruf das komplette Schicht-Activity-Log aller
 * Projekte (ohne Limit) und verwarf das Ergebnis ungenutzt. Der Schichtverlauf wird über den
 * Schichtverlauf-Modal per eigenem Endpunkt geladen; der Tab selbst darf das Log nicht anfassen.
 */
final class ProjectShiftTabActivityLogTest extends FeatureTestCase
{
    #[Test]
    public function shift_tab_does_not_query_the_shift_activity_log(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create(['visible_for_all' => true]);
        $component = Component::create([
            'name' => 'Schichten',
            'type' => ProjectTabComponentEnum::SHIFT_TAB->value,
            'data' => [],
        ]);
        ComponentInTab::create([
            'project_tab_id' => $tab->id,
            'component_id' => $component->id,
            'order' => 1,
        ]);

        $shiftActivityQueries = [];
        DB::listen(function (QueryExecuted $query) use (&$shiftActivityQueries): void {
            if (str_contains($query->sql, 'activity_log') && in_array(Shift::class, $query->bindings, true)) {
                $shiftActivityQueries[] = $query->sql;
            }
        });

        $this->get(route('projects.tab', ['project' => $project->id, 'projectTab' => $tab->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('crafts')->missing('history'));

        $this->assertSame([], $shiftActivityQueries);
    }
}
