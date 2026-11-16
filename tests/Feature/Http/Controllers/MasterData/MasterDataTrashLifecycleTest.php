<?php

namespace Tests\Feature\Http\Controllers\MasterData;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\CollectingSociety\Models\CollectingSociety;
use Artwork\Modules\CompanyType\Models\CompanyType;
use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Currency\Models\Currency;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventProperty;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Sector\Models\Sector;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Funktionstests für Löschen, Wiederherstellen und endgültiges Löschen der Stammdaten
 * sowie für Ereignistyp, Ereignisstatus, Ereigniseigenschaft und Areal.
 */
final class MasterDataTrashLifecycleTest extends FeatureTestCase
{
    /**
     * @return array<string, array{class-string<Model>, string, string}>
     */
    public static function softDeletableMasterData(): array
    {
        return [
            'Kategorie' => [Category::class, '/categories/%d', 'categories'],
            'Genre' => [Genre::class, '/genres/%d', 'genres'],
            'Bereich' => [Sector::class, '/sectors/%d', 'sectors'],
            'Verwertungsgesellschaft' => [CollectingSociety::class, '/collecting_societies/%d', 'collecting_societies'],
            'Unternehmensart' => [CompanyType::class, '/company_types/%d', 'company_types'],
            'Währung' => [Currency::class, '/currencies/%d', 'currencies'],
        ];
    }

    /**
     * @param class-string<Model> $modelClass
     */
    #[Test]
    #[DataProvider('softDeletableMasterData')]
    public function destroy_restore_and_force_delete_run_through_the_trash(
        string $modelClass,
        string $uriPattern,
        string $table
    ): void {
        $this->actingAsAdmin();
        $model = $modelClass::factory()->create();
        $uri = sprintf($uriPattern, $model->id);

        $this->delete($uri)->assertRedirect();
        $this->assertSoftDeleted($table, ['id' => $model->id]);

        $this->patch($uri . '/restore')->assertRedirect(route('projects.settings.trashed'));
        $this->assertNotSoftDeleted($table, ['id' => $model->id]);

        $this->delete($uri)->assertRedirect();
        $this->delete($uri . '/force')->assertRedirect(route('projects.settings.trashed'));
        $this->assertDatabaseMissing($table, ['id' => $model->id]);
    }

    /**
     * @param class-string<Model> $modelClass
     */
    #[Test]
    #[DataProvider('softDeletableMasterData')]
    public function force_delete_and_restore_ignore_entries_that_are_not_in_the_trash(
        string $modelClass,
        string $uriPattern,
        string $table
    ): void {
        $this->actingAsAdmin();
        $model = $modelClass::factory()->create();
        $uri = sprintf($uriPattern, $model->id);

        $this->delete($uri . '/force')->assertNotFound();
        $this->patch($uri . '/restore')->assertNotFound();
        $this->assertNotSoftDeleted($table, ['id' => $model->id]);
    }

    #[Test]
    public function force_deleting_a_category_detaches_it_from_projects(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();
        $project = Project::factory()->create();
        $project->categories()->attach($category->id);
        $category->delete();

        $this->delete(route('categories.force', $category->id))->assertRedirect();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseMissing('category_project', ['category_id' => $category->id]);
        $this->assertModelExists($project);
    }

    #[Test]
    public function currency_used_by_a_contract_is_not_force_deleted(): void
    {
        $this->actingAsAdmin();
        $currency = Currency::factory()->create();
        Contract::factory()->create(['currency_id' => $currency->id]);
        $currency->delete();

        $this->from('/trash')
            ->delete(route('currencies.force', $currency->id))
            ->assertRedirect('/trash')
            ->assertSessionHas('error');

        $this->assertSoftDeleted('currencies', ['id' => $currency->id]);
    }

    #[Test]
    public function company_type_used_by_a_contract_is_not_force_deleted(): void
    {
        $this->actingAsAdmin();
        $companyType = CompanyType::factory()->create();
        Contract::factory()->create(['company_type_id' => $companyType->id]);
        $companyType->delete();

        $this->from('/trash')
            ->delete(route('company_types.force', $companyType->id))
            ->assertRedirect('/trash')
            ->assertSessionHas('error');

        $this->assertSoftDeleted('company_types', ['id' => $companyType->id]);
    }

    #[Test]
    public function destroying_an_event_type_moves_its_events_including_trashed_ones_to_the_fallback_type(): void
    {
        $this->actingAsAdmin();
        $fallback = EventType::query()->find(1) ?? EventType::factory()->create(['id' => 1]);
        $eventType = EventType::factory()->create();
        $event = Event::factory()->create(['event_type_id' => $eventType->id]);
        $trashedEvent = Event::factory()->create(['event_type_id' => $eventType->id]);
        $trashedEvent->delete();

        $this->delete(route('event_types.destroy', $eventType))
            ->assertRedirect(route('event_types.management'));

        $this->assertDatabaseMissing('event_types', ['id' => $eventType->id]);
        $this->assertSame($fallback->id, $event->fresh()->event_type_id);
        $this->assertSame($fallback->id, Event::withTrashed()->find($trashedEvent->id)->event_type_id);
    }

    #[Test]
    public function the_fallback_event_type_cannot_be_destroyed(): void
    {
        $this->actingAsAdmin();
        $fallback = EventType::query()->find(1) ?? EventType::factory()->create(['id' => 1]);

        $this->from('/settings')
            ->delete(route('event_types.destroy', $fallback))
            ->assertRedirect('/settings');

        $this->assertModelExists($fallback);
    }

    #[Test]
    public function relevant_for_inventory_flag_can_be_toggled_and_is_validated(): void
    {
        $this->actingAsAdmin();
        $eventType = EventType::factory()->create(['relevant_for_inventory' => false]);

        $this->patch(route('event-type.update.inventory.relevant', $eventType), ['relevant_for_inventory' => true])
            ->assertOk();
        $this->assertTrue((bool) $eventType->fresh()->relevant_for_inventory);

        $this->patch(route('event-type.update.inventory.relevant', $eventType), ['relevant_for_inventory' => false])
            ->assertOk();
        $this->assertFalse((bool) $eventType->fresh()->relevant_for_inventory);

        $this->patchJson(route('event-type.update.inventory.relevant', $eventType), ['relevant_for_inventory' => 'vielleicht'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('relevant_for_inventory');
        $this->patchJson(route('event-type.update.inventory.relevant', $eventType), [])
            ->assertUnprocessable();
        $this->assertFalse((bool) $eventType->fresh()->relevant_for_inventory);
    }

    #[Test]
    public function updating_an_event_status_as_default_removes_the_previous_default(): void
    {
        $this->actingAsAdmin();
        $previousDefault = EventStatus::factory()->create(['default' => true]);
        $status = EventStatus::factory()->create(['default' => false]);

        $this->patch(route('event_status.update', $status), [
            'id' => $status->id,
            'name' => 'Bestätigt',
            'color' => '#00ff00',
            'default' => true,
        ])->assertOk();

        $status->refresh();
        $this->assertSame('Bestätigt', $status->name);
        $this->assertSame('#00ff00', $status->color);
        $this->assertTrue($status->default);
        $this->assertFalse($previousDefault->fresh()->default);
    }

    #[Test]
    public function updating_an_event_status_validates_name_and_color(): void
    {
        $this->actingAsAdmin();
        $status = EventStatus::factory()->create(['name' => 'Alt', 'color' => '#111111']);

        $this->patchJson(route('event_status.update', $status), [
            'id' => $status->id,
            'name' => '',
            'color' => 'javascript:alert(1)',
            'default' => false,
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'color']);

        $this->assertSame('Alt', $status->fresh()->name);
    }

    #[Test]
    public function destroying_an_event_status_moves_events_to_another_status(): void
    {
        $this->actingAsAdmin();
        $default = EventStatus::factory()->create(['default' => true]);
        $status = EventStatus::factory()->create();
        $event = Event::factory()->create(['event_status_id' => $status->id]);

        $this->delete(route('event_status.delete', $status))->assertOk();

        $this->assertModelMissing($status);
        $this->assertNotNull($event->fresh()->event_status_id);
        $this->assertNotSame($status->id, $event->fresh()->event_status_id);
    }

    #[Test]
    public function destroying_an_event_status_also_moves_trashed_events(): void
    {
        $this->actingAsAdmin();
        EventStatus::factory()->create(['default' => true]);
        $status = EventStatus::factory()->create();
        $trashedEvent = Event::factory()->create(['event_status_id' => $status->id]);
        $trashedEvent->delete();

        $this->delete(route('event_status.delete', $status))->assertOk();

        $this->assertModelMissing($status);
        $this->assertNotSame(
            $status->id,
            Event::withTrashed()->find($trashedEvent->id)->event_status_id
        );
    }

    #[Test]
    public function destroying_the_status_with_id_one_keeps_its_events_valid(): void
    {
        $this->actingAsAdmin();
        $statusOne = EventStatus::query()->find(1) ?? EventStatus::factory()->create(['id' => 1]);
        $other = EventStatus::factory()->create(['default' => true]);
        $event = Event::factory()->create(['event_status_id' => $statusOne->id]);

        $this->delete(route('event_status.delete', $statusOne))->assertOk();

        $this->assertModelMissing($statusOne);
        $this->assertSame($other->id, $event->fresh()->event_status_id);
    }

    #[Test]
    public function updating_an_event_property_changes_only_name_and_icon(): void
    {
        $this->actingAsAdmin();
        $property = EventProperty::factory()->create(['name' => 'Alt', 'icon' => 'IconStar']);

        $this->from('/settings')->patch(route('event_settings.event_properties.update', $property), [
            'id' => $property->id,
            'name' => 'Neu',
            'icon' => 'IconHeart',
        ])->assertRedirect('/settings');

        $property->refresh();
        $this->assertSame('Neu', $property->name);
        $this->assertSame('IconHeart', $property->icon);
    }

    #[Test]
    public function updating_an_event_property_validates_name_and_icon(): void
    {
        $this->actingAsAdmin();
        $property = EventProperty::factory()->create(['name' => 'Alt', 'icon' => 'IconStar']);

        $this->patchJson(route('event_settings.event_properties.update', $property), [
            'id' => $property->id,
            'name' => '',
            'icon' => '<svg onload=alert(1)>',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'icon']);

        $this->assertSame('Alt', $property->fresh()->name);
    }

    #[Test]
    public function destroying_an_event_property_removes_it_from_events(): void
    {
        $this->actingAsAdmin();
        $property = EventProperty::factory()->create();
        $event = Event::factory()->create();
        $event->eventProperties()->attach($property->id);

        $this->from('/settings')
            ->delete(route('event_settings.event_properties.delete', $property))
            ->assertRedirect('/settings');

        $this->assertDatabaseMissing('event_properties', ['id' => $property->id]);
        $this->assertDatabaseMissing('event_event_property', ['event_property_id' => $property->id]);
    }

    #[Test]
    public function destroying_an_area_moves_its_rooms_to_the_trash(): void
    {
        $this->actingAsAdmin();
        $area = Area::factory()->create();
        $room = Room::factory()->create(['area_id' => $area->id]);

        $this->delete('/areas/' . $area->id)->assertRedirect(route('areas.management'));

        $this->assertSoftDeleted('areas', ['id' => $area->id]);
        $this->assertSoftDeleted('rooms', ['id' => $room->id]);
    }

    #[Test]
    public function an_area_destroyed_with_rooms_can_be_restored_and_force_deleted(): void
    {
        $this->actingAsAdmin();
        $area = Area::factory()->create();
        $room = Room::factory()->create(['area_id' => $area->id]);

        $this->delete('/areas/' . $area->id)->assertRedirect();
        $this->patch(route('areas.restore', $area->id))->assertRedirect();
        $this->assertNotSoftDeleted('rooms', ['id' => $room->id]);

        $this->delete('/areas/' . $area->id)->assertRedirect();
        $this->delete(route('areas.force', $area->id))->assertRedirect(route('areas.trashed'));

        $this->assertDatabaseMissing('areas', ['id' => $area->id]);
        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }
}
