<?php

namespace Tests\Feature\Modules\Room;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Change\DTO\ChangeDTO;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Exports\RoomListExport;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Models\RoomAttribute;
use Artwork\Modules\Room\Models\RoomCategory;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Räume & Areale: Raumkategorien, Raumeigenschaften, Sortierung, Verschieben, Sammelaktionen und Export.
 */
final class RoomSettingsListFeaturesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsUserWith(PermissionEnum::ROOM_UPDATE->value);
    }

    private function historyEntries(Room $room, string $translationKey): int
    {
        return app(ChangeService::class)->historyFor($room)
            ->filter(static fn (ChangeDTO $change): bool => str_contains(
                (string) json_encode($change->properties),
                json_encode($translationKey)
            ))
            ->count();
    }

    #[Test]
    public function a_room_category_needs_a_unique_name(): void
    {
        $this->from(route('areas.management'))
            ->post(route('room_categories.store'), ['name' => '  Probebühne  '])
            ->assertRedirect(route('areas.management'))
            ->assertSessionHasNoErrors();
        $this->assertSame(1, RoomCategory::where('name', 'Probebühne')->count());

        $this->post(route('room_categories.store'), ['name' => 'probebühne'])->assertSessionHasErrors('name');
        $this->post(route('room_categories.store'), ['name' => '   '])->assertSessionHasErrors('name');
        $this->post(route('room_categories.store'), ['name' => str_repeat('a', 256)])->assertSessionHasErrors('name');
        $this->post(route('room_categories.store'), [])->assertSessionHasErrors('name');

        $this->assertSame(1, RoomCategory::where('name', 'like', 'probebühne')->count());
    }

    #[Test]
    public function a_room_property_needs_a_unique_name(): void
    {
        $this->from(route('areas.management'))
            ->post(route('room_attribute.store'), ['name' => 'Tageslicht'])
            ->assertRedirect(route('areas.management'))
            ->assertSessionHasNoErrors();

        $this->post(route('room_attribute.store'), ['name' => 'Tageslicht'])->assertSessionHasErrors('name');
        $this->post(route('room_attribute.store'), ['name' => ''])->assertSessionHasErrors('name');

        $this->assertSame(1, RoomAttribute::where('name', 'Tageslicht')->count());
    }

    #[Test]
    public function categories_and_properties_require_the_room_permission(): void
    {
        $this->actingAsUserWith([]);

        $this->post(route('room_categories.store'), ['name' => 'Verboten'])->assertForbidden();
        $this->post(route('room_attribute.store'), ['name' => 'Verboten'])->assertForbidden();

        $this->assertSame(0, RoomCategory::where('name', 'Verboten')->count());
        $this->assertSame(0, RoomAttribute::where('name', 'Verboten')->count());
    }

    #[Test]
    public function the_sort_page_lists_rooms_with_their_area_and_saves_positions(): void
    {
        $area = Area::factory()->create(['name' => 'Sortierareal']);
        $first = Room::factory()->create(['area_id' => $area->id, 'position' => 1]);
        $second = Room::factory()->create(['area_id' => $area->id, 'position' => 2]);

        $rooms = collect($this->get(route('rooms.move'))->assertOk()->viewData('page')['props']['rooms']);
        $this->assertSame('Sortierareal', $rooms->firstWhere('id', $first->id)['area']['name']);

        $this->from(route('rooms.move'))->post(route('rooms.order.new'), [
            'rooms' => [
                ['id' => $second->id, 'position' => 1],
                ['id' => $first->id, 'position' => 2],
            ],
        ])->assertRedirect(route('rooms.move'))->assertSessionHasNoErrors();

        $this->assertSame(2, $first->fresh()->position);
        $this->assertSame(1, $second->fresh()->position);
    }

    #[Test]
    public function saving_positions_rejects_incomplete_input_instead_of_failing(): void
    {
        $room = Room::factory()->create(['position' => 5]);

        $this->post(route('rooms.order.new'), [])->assertSessionHasErrors('rooms');
        $this->post(route('rooms.order.new'), ['rooms' => [['id' => $room->id]]])
            ->assertSessionHasErrors('rooms.0.position');
        $this->post(route('rooms.order.new'), ['rooms' => [['id' => $room->id, 'position' => -3]]])
            ->assertSessionHasErrors('rooms.0.position');

        $this->assertSame(5, $room->fresh()->position);
    }

    #[Test]
    public function categories_and_properties_can_be_renamed_and_keep_their_rooms(): void
    {
        $room = Room::factory()->create();
        $category = RoomCategory::create(['name' => 'Bühne']);
        $other = RoomCategory::create(['name' => 'Lager']);
        $property = RoomAttribute::create(['name' => 'Klima']);
        $room->categories()->attach($category->id);
        $room->attributes()->attach($property->id);

        $this->from(route('areas.management'))
            ->patch(route('room_categories.update', $category), ['name' => 'Hauptbühne'])
            ->assertRedirect(route('areas.management'))
            ->assertSessionHasNoErrors();
        // derselbe Name in anderer Schreibweise ist für den eigenen Eintrag erlaubt, ein fremder Name nicht
        $this->patch(route('room_categories.update', $category), ['name' => 'HAUPTBÜHNE'])
            ->assertSessionHasNoErrors();
        $this->patch(route('room_categories.update', $category), ['name' => 'Lager'])->assertSessionHasErrors('name');
        $this->patch(route('room_categories.update', $category), ['name' => ' '])->assertSessionHasErrors('name');
        $this->patch(route('room_attribute.update', $property), ['name' => 'Klimaanlage'])
            ->assertSessionHasNoErrors();

        $this->assertSame('HAUPTBÜHNE', $category->fresh()->name);
        $this->assertSame('Lager', $other->fresh()->name);
        $this->assertSame('Klimaanlage', $property->fresh()->name);
        $this->assertSame([$category->id], $room->categories()->pluck('room_categories.id')->all());
        $this->assertSame([$property->id], $room->attributes()->pluck('room_attributes.id')->all());

        $this->actingAsUserWith([]);
        $this->patch(route('room_categories.update', $category), ['name' => 'Verboten'])->assertForbidden();
        $this->patch(route('room_attribute.update', $property), ['name' => 'Verboten'])->assertForbidden();
    }

    #[Test]
    public function a_room_moves_to_another_area_and_the_history_records_it(): void
    {
        $from = Area::factory()->create(['name' => 'Altbau']);
        $to = Area::factory()->create(['name' => 'Neubau']);
        $trashed = Area::factory()->create();
        $trashed->delete();
        $room = Room::factory()->create(['area_id' => $from->id, 'name' => 'Studio']);

        $this->patch(route('rooms.update', $room), ['name' => 'Studio', 'area_id' => $trashed->id])
            ->assertSessionHasErrors('area_id');
        $this->patch(route('rooms.update', $room), ['name' => 'Studio', 'area_id' => 999999])
            ->assertSessionHasErrors('area_id');
        $this->assertSame($from->id, $room->fresh()->area_id);

        $this->patch(route('rooms.update', $room), ['name' => 'Studio 1', 'area_id' => $to->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($to->id, $room->fresh()->area_id);
        $this->assertSame('Studio 1', $room->fresh()->name);
        $this->assertSame(1, $this->historyEntries($room, 'Moved from area {0} to area {1}'));
    }

    #[Test]
    public function a_room_admin_without_room_management_cannot_move_the_room(): void
    {
        $from = Area::factory()->create();
        $to = Area::factory()->create();
        $room = Room::factory()->create(['area_id' => $from->id, 'name' => 'Studio']);
        $admin = $this->actingAsUserWith([]);
        $room->users()->attach($admin->id, ['is_admin' => true]);

        $this->patch(route('rooms.update', $room), ['name' => 'Umbenannt', 'area_id' => $from->id])
            ->assertSessionHasNoErrors();
        $this->assertSame('Umbenannt', $room->fresh()->name);

        $this->patch(route('rooms.update', $room), ['name' => 'Verschoben', 'area_id' => $to->id])
            ->assertForbidden();
        $this->assertSame($from->id, $room->fresh()->area_id);
        $this->assertSame('Umbenannt', $room->fresh()->name);
    }

    #[Test]
    public function areas_keep_a_saved_order_and_new_areas_go_to_the_end(): void
    {
        $first = Area::factory()->create(['name' => 'Eins', 'position' => 900]);
        $second = Area::factory()->create(['name' => 'Zwei', 'position' => 901]);
        $third = Area::factory()->create(['name' => 'Drei', 'position' => 902]);

        $this->from(route('rooms.move'))
            ->post(route('areas.order'), ['areas' => [$third->id, $first->id, $second->id]])
            ->assertRedirect(route('rooms.move'))
            ->assertSessionHasNoErrors();
        $this->post(route('areas.order'), ['areas' => [$first->id, 999999]])->assertSessionHasErrors('areas.1');
        $this->post(route('areas.order'), [])->assertSessionHasErrors('areas');

        $names = fn (string $routeName): array => collect(
            $this->get(route($routeName))->assertOk()->viewData('page')['props']['areas']
        )->whereIn('id', [$first->id, $second->id, $third->id])->pluck('name')->values()->all();
        $this->assertSame(['Drei', 'Eins', 'Zwei'], $names('areas.management'));
        $this->assertSame(['Drei', 'Eins', 'Zwei'], $names('rooms.move'));

        $this->post(route('areas.store'), ['name' => 'Ganz neu'])->assertSessionHasNoErrors();
        $this->assertSame(
            Area::max('position'),
            Area::where('name', 'Ganz neu')->firstOrFail()->position
        );

        $this->actingAsUserWith([]);
        $this->post(route('areas.order'), ['areas' => [$first->id]])->assertForbidden();
    }

    #[Test]
    public function bulk_update_assigns_and_removes_categories_and_properties(): void
    {
        $first = Room::factory()->create();
        $second = Room::factory()->create();
        $kept = RoomCategory::create(['name' => 'Bleibt']);
        $added = RoomCategory::create(['name' => 'Neu dazu']);
        $property = RoomAttribute::create(['name' => 'Tageslicht']);
        $first->categories()->attach($kept->id);
        $route = route('rooms.bulk-update');
        $ids = [$first->id, $second->id];

        $this->postJson($route, ['ids' => $ids, 'action' => 'add_categories', 'category_ids' => [$added->id]])
            ->assertOk()->assertExactJson(['updated' => 2]);
        // zweimal zuweisen legt keine doppelte Zuordnung an
        $this->postJson($route, ['ids' => $ids, 'action' => 'add_categories', 'category_ids' => [$added->id]])
            ->assertOk();
        $this->assertEqualsCanonicalizing(
            [$kept->id, $added->id],
            $first->categories()->pluck('room_categories.id')->all()
        );
        $this->assertSame([$added->id], $second->categories()->pluck('room_categories.id')->all());
        $this->assertSame(1, $this->historyEntries($second, 'Added category'));

        $this->postJson($route, ['ids' => $ids, 'action' => 'remove_categories', 'category_ids' => [$added->id]])
            ->assertOk();
        $this->assertSame([$kept->id], $first->categories()->pluck('room_categories.id')->all());
        $this->assertSame([], $second->categories()->pluck('room_categories.id')->all());

        $this->postJson($route, ['ids' => $ids, 'action' => 'add_attributes', 'attribute_ids' => [$property->id]])
            ->assertOk();
        $this->assertSame([$property->id], $second->attributes()->pluck('room_attributes.id')->all());
        $this->postJson($route, [
            'ids' => [$first->id],
            'action' => 'remove_attributes',
            'attribute_ids' => [$property->id],
        ])->assertOk();
        $this->assertSame([], $first->attributes()->pluck('room_attributes.id')->all());
        $this->assertSame([$property->id], $second->attributes()->pluck('room_attributes.id')->all());
    }

    #[Test]
    public function bulk_update_moves_rooms_to_another_area(): void
    {
        $from = Area::factory()->create();
        $to = Area::factory()->create();
        $first = Room::factory()->create(['area_id' => $from->id]);
        $alreadyThere = Room::factory()->create(['area_id' => $to->id]);

        $this->postJson(route('rooms.bulk-update'), [
            'ids' => [$first->id, $alreadyThere->id],
            'action' => 'move',
            'area_id' => $to->id,
        ])->assertOk();

        $this->assertSame($to->id, $first->fresh()->area_id);
        $this->assertSame($to->id, $alreadyThere->fresh()->area_id);
        $this->assertSame(1, $this->historyEntries($first, 'Moved from area {0} to area {1}'));
        $this->assertSame(0, $this->historyEntries($alreadyThere, 'Moved from area {0} to area {1}'));
    }

    #[Test]
    public function bulk_update_rejects_incomplete_or_unknown_input_and_changes_nothing(): void
    {
        $room = Room::factory()->create();
        $category = RoomCategory::create(['name' => 'Probe']);
        $route = route('rooms.bulk-update');

        $this->postJson($route, ['ids' => [], 'action' => 'move'])->assertUnprocessable();
        $this->postJson($route, ['ids' => [$room->id], 'action' => 'unknown'])->assertUnprocessable();
        $this->postJson($route, ['ids' => [$room->id], 'action' => 'add_categories'])->assertUnprocessable();
        $this->postJson($route, ['ids' => [$room->id], 'action' => 'add_categories', 'category_ids' => [999999]])
            ->assertUnprocessable();
        $this->postJson($route, ['ids' => [$room->id], 'action' => 'move'])->assertUnprocessable();
        $this->postJson($route, [
            'ids' => [$room->id, 999999],
            'action' => 'add_categories',
            'category_ids' => [$category->id],
        ])->assertNotFound();

        $this->assertSame(0, $room->categories()->count());
    }

    #[Test]
    public function bulk_delete_moves_the_selection_to_the_trash_or_nothing_at_all(): void
    {
        $first = Room::factory()->create();
        $second = Room::factory()->create();
        $kept = Room::factory()->create();
        $route = route('rooms.bulk-delete');

        $this->postJson($route, ['ids' => [$first->id, 999999]])->assertNotFound();
        $this->postJson($route, ['ids' => []])->assertUnprocessable();
        $this->assertNotNull(Room::find($first->id));

        $this->postJson($route, ['ids' => [$first->id, $second->id]])
            ->assertOk()->assertExactJson(['deleted' => 2]);

        $this->assertSoftDeleted($first);
        $this->assertSoftDeleted($second);
        $this->assertNotNull(Room::find($kept->id));
    }

    #[Test]
    public function bulk_actions_and_the_export_require_room_management(): void
    {
        $room = Room::factory()->create();
        $category = RoomCategory::create(['name' => 'Gesperrt']);
        $this->actingAsUserWith([]);

        $this->postJson(route('rooms.bulk-delete'), ['ids' => [$room->id]])->assertForbidden();
        $this->postJson(route('rooms.bulk-update'), [
            'ids' => [$room->id],
            'action' => 'add_categories',
            'category_ids' => [$category->id],
        ])->assertForbidden();
        $this->get(route('rooms.export'))->assertForbidden();

        $this->assertNotNull(Room::find($room->id));
        $this->assertSame(0, $room->categories()->count());
    }

    #[Test]
    public function the_export_lists_rooms_by_area_with_their_details(): void
    {
        $area = Area::factory()->create(['name' => 'Exportareal', 'position' => 9000]);
        $room = Room::factory()->create([
            'area_id' => $area->id,
            'name' => 'Exportraum',
            'capacity' => 120,
            'city' => 'Hamburg',
            'position' => 1,
            'temporary' => false,
            'relevant_for_disposition' => true,
        ]);
        $neighbour = Room::factory()->create(['area_id' => $area->id, 'name' => 'Nebenan', 'position' => 2]);
        $room->categories()->attach(RoomCategory::create(['name' => 'Saal'])->id);
        $room->attributes()->attach(RoomAttribute::create(['name' => 'Barrierefrei'])->id);
        $room->adjoining_rooms()->attach($neighbour->id);
        $admin = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Admin']);
        $room->users()->attach($admin->id, ['is_admin' => true]);
        $team = Department::factory()->create(['name' => 'Haustechnik']);
        $room->departments()->attach($team->id, ['is_admin' => true]);

        $response = $this->get(route('rooms.export', ['ids' => $room->id . ',' . $neighbour->id]))->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
        $this->get(route('rooms.export'))->assertOk();

        $export = new RoomListExport(
            Room::query()->whereKey([$room->id, $neighbour->id])
                ->with(['area', 'categories', 'attributes', 'adjoining_rooms', 'adminDepartments'])
                ->orderBy('position')->get()
        );
        $rows = $export->array();

        $this->assertCount(2, $rows);
        $this->assertCount(count($export->headings()), $rows[0]);
        $this->assertSame(['Exportareal', 'Exportraum'], [$rows[0][0], $rows[0][1]]);
        $this->assertSame(120, $rows[0][3]);
        $this->assertSame('Hamburg', $rows[0][6]);
        $this->assertSame('Saal', $rows[0][7]);
        $this->assertSame('Barrierefrei', $rows[0][8]);
        $this->assertSame('Nebenan', $rows[0][9]);
        $this->assertSame('Ada Admin, Haustechnik', $rows[0][10]);
        $this->assertSame('', $rows[1][10]);
        $this->assertSame(__('Yes'), $rows[0][12]);
        $this->assertSame(__('No'), $rows[0][13]);
        $this->assertNull($rows[0][14]);
    }
}
