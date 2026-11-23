<?php

namespace Tests\Feature\Http\Controllers\MasterData;

use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\Project\Models\ProjectState;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Stammdaten der Projekteinstellungen: leere und zu lange Namen werden als Validierungsfehler
 * abgelehnt. Vorher endete ein leerer Name in einem Datenbankfehler (500). Gleiche Namen bleiben erlaubt.
 */
final class MasterDataNameValidationTest extends FeatureTestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function stores(): array
    {
        return [
            'genres' => ['genres.store', 'genres'],
            'categories' => ['categories.store', 'categories'],
            'sectors' => ['sectors.store', 'sectors'],
            'contract types' => ['contract_types.store', 'contract_types'],
            'company types' => ['company_types.store', 'company_types'],
            'currencies' => ['currencies.store', 'currencies'],
            'project states' => ['state.store', 'project_states'],
        ];
    }

    #[Test]
    #[DataProvider('stores')]
    public function an_empty_name_is_rejected(string $route, string $table): void
    {
        $this->actingAsAdmin();
        $before = DB::table($table)->count();

        $this->postJson(route($route), ['name' => '   ', 'color' => '#123456'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->assertSame($before, DB::table($table)->count());
    }

    #[Test]
    #[DataProvider('stores')]
    public function a_name_longer_than_the_column_is_rejected(string $route, string $table): void
    {
        $this->actingAsAdmin();

        $this->postJson(route($route), ['name' => str_repeat('a', 256), 'color' => '#123456'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    #[Test]
    #[DataProvider('stores')]
    public function the_same_name_may_be_used_twice(string $route, string $table): void
    {
        // Dubletten können gewollt sein
        $this->actingAsAdmin();

        $this->postJson(route($route), ['name' => 'Doppelprobe', 'color' => '#123456'])->assertValid();
        $this->postJson(route($route), ['name' => 'Doppelprobe', 'color' => '#654321'])->assertValid();

        $this->assertSame(2, DB::table($table)->where('name', 'Doppelprobe')->count());
    }

    #[Test]
    public function an_entry_keeps_its_own_name_when_only_the_colour_changes(): void
    {
        $this->actingAsAdmin();
        $category = Category::create(['name' => 'Farbprobe', 'color' => '#111111']);

        $this->patchJson(route('categories.update', $category), ['name' => 'Farbprobe', 'color' => '#222222'])
            ->assertValid();
        $this->patchJson(route('categories.update', $category), ['color' => '#333333'])->assertValid();

        $this->assertSame('#333333', $category->fresh()->color);
    }

    #[Test]
    public function an_entry_cannot_be_renamed_to_an_empty_name(): void
    {
        $this->actingAsAdmin();
        $state = ProjectState::create(['name' => 'Anders', 'color' => '#111111']);

        $this->patchJson(route('state.update', $state), ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->assertSame('Anders', $state->fresh()->name);
    }

    #[Test]
    public function the_settings_page_reports_how_often_entries_are_used(): void
    {
        $this->actingAsAdmin();
        $used = ProjectState::create(['name' => 'Zählprobe genutzt', 'color' => '#111111']);
        $unused = ProjectState::create(['name' => 'Zählprobe frei', 'color' => '#111111']);
        \Artwork\Modules\Project\Models\Project::factory()->count(2)->create(['state' => $used->id]);
        \Artwork\Modules\Project\Models\Project::factory()->create(['state' => $used->id])->delete();

        $states = collect($this->get(route('project.settings'))->assertOk()->viewData('page')['props']['states']);

        $this->assertSame(2, $states->firstWhere('id', $used->id)['usage_count']);
        $this->assertSame(0, $states->firstWhere('id', $unused->id)['usage_count']);
    }

    #[Test]
    public function the_event_type_page_reports_how_many_events_use_a_type(): void
    {
        $this->actingAsAdmin();
        $eventType = \Artwork\Modules\EventType\Models\EventType::factory()->create();
        \Artwork\Modules\Event\Models\Event::factory()->count(3)->create(['event_type_id' => $eventType->id]);

        $types = collect($this->get(route('event_types.management'))->assertOk()->viewData('page')['props']['event_types']);

        $this->assertSame(3, $types->firstWhere('id', $eventType->id)['events_count']);
    }
}
