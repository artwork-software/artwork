<?php

namespace Tests\Feature\Projects;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Projektverlauf: Verursacher werden gesammelt geladen und nur mit Avatar-Feldern ausgeliefert
 * (Kontaktdaten lädt der Tooltip nach).
 */
final class ProjectHistoryChangersTest extends FeatureTestCase
{
    #[Test]
    public function changers_are_slim_and_loaded_with_a_constant_number_of_user_queries(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $authors = User::factory()->count(6)->create();
        foreach ($authors as $author) {
            foreach (range(1, 3) as $entry) {
                activity()->performedOn($project)->causedBy($author)->log('Eintrag ' . $entry);
            }
        }

        DB::enableQueryLog();
        $history = $this->getJson(route('projects.history', $project))->assertOk()->json('history');
        $userQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_starts_with($query['query'], 'select * from `users` where `id`'))
            ->count();
        DB::disableQueryLog();

        // eine Abfrage für alle Verursacher (früher eine je Eintrag)
        $this->assertSame(1, $userQueries);
        $changers = collect($history)->pluck('changer')->filter()->values();
        $this->assertGreaterThanOrEqual(18, $changers->count());
        $this->assertEqualsCanonicalizing(
            ['id', 'first_name', 'last_name', 'full_name', 'profile_photo_url'],
            array_keys($changers->first())
        );
        foreach ($authors as $author) {
            $this->assertSame(3, $changers->where('id', $author->id)->count());
        }
    }
}
