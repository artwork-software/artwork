<?php

namespace Tests\Feature\Modules\User\Characterization;

use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\PdfExportUserFilter;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserCommentedBudgetItemsSetting;
use Artwork\Modules\User\Models\UserFilterTemplate;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Charakterisierung gespeicherter Nutzer-Vorlagen: Kalender-Filtervorlagen (UserFilterTemplateController),
 * PDF-Export-Filter (PdfExportUserFilterController) und die Einstellung "kommentierte Budgetposten
 * ausblenden" (UserCommentedBudgetItemsSettingController@update). Alles strikt pro eigenem Account.
 */
final class UserSavedFilterEndpointsTest extends FeatureTestCase
{
    #[Test]
    public function filter_template_is_stored_for_the_own_account_with_empty_lists_as_null(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $eventType = EventType::factory()->create();
        $this->actingAs($user);

        $this->postJson(route('filter.store', $user), [
            'name' => 'Probebühnen',
            'filter_type' => 'calendar_filter',
            'room_ids' => [$room->id],
            'event_type_ids' => [$eventType->id],
            'area_ids' => [],
        ])->assertOk();

        $template = $user->userFilterTemplates()->sole();
        $this->assertSame('Probebühnen', $template->name);
        $this->assertSame('calendar_filter', $template->filter_type);
        $this->assertSame([$room->id], $template->room_ids);
        $this->assertSame([$eventType->id], $template->event_type_ids);
        $this->assertNull($template->area_ids);
        $this->assertNull($template->craft_ids);
    }

    #[Test]
    public function filter_template_store_validates_type_and_referenced_ids(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->postJson(route('filter.store', $user), [
            'name' => 'x',
            'filter_type' => 'unknown_filter',
            'room_ids' => [999999999],
        ])->assertUnprocessable()->assertJsonValidationErrors(['filter_type', 'room_ids.0']);

        $this->assertSame(0, $user->userFilterTemplates()->count());
    }

    #[Test]
    public function filter_templates_cannot_be_stored_in_other_accounts(): void
    {
        $owner = User::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->postJson(route('filter.store', $owner), [
            'name' => 'fremd',
            'filter_type' => 'shift_filter',
        ])->assertForbidden();

        $this->assertSame(0, $owner->userFilterTemplates()->count());
    }

    #[Test]
    public function own_filter_templates_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $template = UserFilterTemplate::factory()->for($user)->create(['filter_type' => 'calendar_filter']);
        $this->actingAs($user);

        $this->deleteJson(route('filter.destroy', $template))->assertOk();

        $this->assertModelMissing($template);
    }

    #[Test]
    public function filter_templates_of_other_users_cannot_be_deleted(): void
    {
        $template = UserFilterTemplate::factory()->create(['filter_type' => 'calendar_filter']);
        $this->actingAs(User::factory()->create());

        $this->deleteJson(route('filter.destroy', $template))->assertForbidden();

        $this->assertModelExists($template);
    }

    #[Test]
    public function pdf_export_filters_are_listed_only_for_the_own_account_sorted_by_name(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        PdfExportUserFilter::query()->create(['user_id' => $user->id, 'name' => 'Zeta', 'filters' => []]);
        PdfExportUserFilter::query()->create(['user_id' => $user->id, 'name' => 'Alpha', 'filters' => ['a' => 1]]);
        PdfExportUserFilter::query()->create(['user_id' => $other->id, 'name' => 'Fremd', 'filters' => []]);
        $this->actingAs($user);

        $response = $this->getJson(route('pdf-export-user-filters.index'))->assertOk();

        $this->assertSame(['Alpha', 'Zeta'], array_column($response->json(), 'name'));
    }

    #[Test]
    public function pdf_export_filter_is_stored_for_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->postJson(route('pdf-export-user-filters.store'), [
            'name' => 'Wochenplan',
            'filters' => ['rooms' => [1, 2]],
            'user_id' => User::factory()->create()->id,
        ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('filter.name', 'Wochenplan');

        $filter = PdfExportUserFilter::query()->where('name', 'Wochenplan')->sole();
        $this->assertSame($user->id, $filter->user_id);
        $this->assertSame(['rooms' => [1, 2]], $filter->filters);
    }

    #[Test]
    public function pdf_export_filter_without_filters_is_stored_with_an_empty_array(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->postJson(route('pdf-export-user-filters.store'), ['name' => 'Leer'])->assertOk();

        $this->assertSame([], PdfExportUserFilter::query()->where('user_id', $user->id)->sole()->filters);
    }

    #[Test]
    public function pdf_export_filter_name_is_required_and_limited(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson(route('pdf-export-user-filters.store'), ['name' => str_repeat('a', 81)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
        $this->postJson(route('pdf-export-user-filters.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    #[Test]
    public function own_pdf_export_filters_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $filter = PdfExportUserFilter::query()->create(['user_id' => $user->id, 'name' => 'x', 'filters' => []]);
        $this->actingAs($user);

        $this->deleteJson(route('pdf-export-user-filters.destroy', $filter))
            ->assertOk()
            ->assertExactJson(['ok' => true]);

        $this->assertModelMissing($filter);
    }

    #[Test]
    public function pdf_export_filters_of_other_users_cannot_be_deleted(): void
    {
        $filter = PdfExportUserFilter::query()->create([
            'user_id' => User::factory()->create()->id,
            'name' => 'x',
            'filters' => [],
        ]);
        $this->actingAs(User::factory()->create());

        $this->deleteJson(route('pdf-export-user-filters.destroy', $filter))->assertForbidden();

        $this->assertModelExists($filter);
    }

    #[Test]
    public function commented_budget_items_setting_can_be_updated_by_its_owner(): void
    {
        $user = User::factory()->create();
        $setting = UserCommentedBudgetItemsSetting::factory()->create(['user_id' => $user->id, 'exclude' => false]);
        $this->actingAs($user);

        $this->patchJson(route('user.commentedBudgetItemsSettings.update', [$user, $setting]), ['exclude' => true])
            ->assertOk();

        $this->assertTrue((bool) $setting->fresh()->exclude);
    }

    #[Test]
    public function commented_budget_items_setting_requires_a_boolean(): void
    {
        $user = User::factory()->create();
        $setting = UserCommentedBudgetItemsSetting::factory()->create(['user_id' => $user->id, 'exclude' => false]);
        $this->actingAs($user);

        $this->patchJson(route('user.commentedBudgetItemsSettings.update', [$user, $setting]), ['exclude' => 'ja'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('exclude');
    }

    #[Test]
    public function commented_budget_items_setting_of_other_users_cannot_be_updated(): void
    {
        $owner = User::factory()->create();
        $setting = UserCommentedBudgetItemsSetting::factory()->create(['user_id' => $owner->id, 'exclude' => false]);
        $this->actingAs(User::factory()->create());

        $this->patchJson(route('user.commentedBudgetItemsSettings.update', [$owner, $setting]), ['exclude' => true])
            ->assertForbidden();

        $this->assertFalse((bool) $setting->fresh()->exclude);
    }

    #[Test]
    public function commented_budget_items_setting_of_another_user_cannot_be_smuggled_via_the_own_route(): void
    {
        $actor = User::factory()->create();
        $foreignSetting = UserCommentedBudgetItemsSetting::factory()->create(['exclude' => false]);
        $this->actingAs($actor);

        $this->patchJson(route('user.commentedBudgetItemsSettings.update', [$actor, $foreignSetting]), [
            'exclude' => true,
        ])->assertForbidden();

        $this->assertFalse((bool) $foreignSetting->fresh()->exclude);
    }
}
