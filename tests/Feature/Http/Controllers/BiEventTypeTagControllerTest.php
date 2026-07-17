<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\BusinessIntelligence\Models\BiEventTypeTag;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class BiEventTypeTagControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_list_tags(): void
    {
        $this->getJson(route('bi.tags.index'))->assertUnauthorized();
    }

    #[Test]
    public function user_without_permission_cannot_manage_tags(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson(route('bi.tags.index'))->assertForbidden();
        $this->postJson(route('bi.tags.store'), [
            'name' => 'Performance',
            'name_de' => 'Vorstellung',
        ])->assertForbidden();
    }

    #[Test]
    public function user_with_permission_can_create_tag(): void
    {
        $this->actingAsUserWith(PermissionEnum::EVENT_SETTINGS_UPDATE->value);

        $this->postJson(route('bi.tags.store'), [
            'name' => 'Performance',
            'name_de' => 'Vorstellung',
            'color' => '#22c55e',
        ])->assertCreated();

        $this->assertDatabaseHas('bi_event_type_tags', [
            'name' => 'Performance',
            'name_de' => 'Vorstellung',
            'color' => '#22c55e',
        ]);
    }

    #[Test]
    public function user_with_permission_can_update_tag(): void
    {
        $this->actingAsUserWith(PermissionEnum::EVENT_SETTINGS_UPDATE->value);
        $tag = BiEventTypeTag::create(['name' => 'Old', 'name_de' => 'Alt', 'color' => '#000000']);

        $this->putJson(route('bi.tags.update', $tag), [
            'name' => 'New',
            'name_de' => 'Neu',
            'color' => '#ffffff',
        ])->assertOk();

        $this->assertDatabaseHas('bi_event_type_tags', [
            'id' => $tag->id,
            'name' => 'New',
            'name_de' => 'Neu',
            'color' => '#ffffff',
        ]);
    }

    #[Test]
    public function user_with_permission_can_sync_event_types(): void
    {
        $this->actingAsUserWith(PermissionEnum::EVENT_SETTINGS_UPDATE->value);
        $tag = BiEventTypeTag::create(['name' => 'Performance', 'name_de' => 'Vorstellung']);
        $eventType = EventType::query()->first() ?? EventType::factory()->create();

        $this->postJson(route('bi.tags.sync-event-types', $tag), [
            'event_type_ids' => [$eventType->id],
        ])->assertOk();

        $this->assertDatabaseHas('bi_event_type_tag_event_type', [
            'bi_event_type_tag_id' => $tag->id,
            'event_type_id' => $eventType->id,
        ]);
    }

    #[Test]
    public function sync_accepts_empty_array_to_unlink_all_event_types(): void
    {
        $this->actingAsUserWith(PermissionEnum::EVENT_SETTINGS_UPDATE->value);
        $tag = BiEventTypeTag::create(['name' => 'Performance', 'name_de' => 'Vorstellung']);
        $eventType = EventType::query()->first() ?? EventType::factory()->create();
        $tag->eventTypes()->sync([$eventType->id]);

        $this->postJson(route('bi.tags.sync-event-types', $tag), [
            'event_type_ids' => [],
        ])->assertOk();

        $this->assertDatabaseMissing('bi_event_type_tag_event_type', [
            'bi_event_type_tag_id' => $tag->id,
        ]);
    }

    #[Test]
    public function user_with_permission_can_delete_tag(): void
    {
        $this->actingAsUserWith(PermissionEnum::EVENT_SETTINGS_UPDATE->value);
        $tag = BiEventTypeTag::create(['name' => 'Performance', 'name_de' => 'Vorstellung']);

        $this->deleteJson(route('bi.tags.destroy', $tag))->assertNoContent();

        $this->assertDatabaseMissing('bi_event_type_tags', ['id' => $tag->id]);
    }
}
