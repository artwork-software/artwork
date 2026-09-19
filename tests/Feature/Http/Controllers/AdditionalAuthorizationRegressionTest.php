<?php

namespace Tests\Feature\Http\Controllers;

use App\Settings\ShiftSettings;
use Artwork\Modules\Checklist\Http\Resources\ChecklistIndexResource;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Models\MoneySourceCategory;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPreset;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class AdditionalAuthorizationRegressionTest extends FeatureTestCase
{
    #[Test]
    #[DataProvider('profileAccess')]
    public function profile_fields_follow_their_permissions(string $access): void
    {
        $target = User::factory()->create([
            'email' => 'private@example.test', 'email_private' => true,
            'phone_number' => '0123456789', 'phone_private' => true,
            'salary_per_hour' => 123, 'salary_description' => 'Confidential',
            'temporary' => true, 'employStart' => '2026-01-01', 'employEnd' => '2026-12-31',
        ]);
        $actor = $access === 'self' ? $target : User::factory()->create();
        $this->actingAs($actor);
        if ($access === 'private') {
            $this->actingAsUserWith(PermissionEnum::CAN_VIEW_PRIVATE_USER_INFO->value, $actor);
        } elseif ($access === 'manager') {
            $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value, $actor);
        } elseif ($access === 'admin') {
            $this->actingAsAdmin($actor);
        }
        $canViewContacts = in_array($access, ['self', 'private', 'admin'], true);
        $canViewTerms = in_array($access, ['manager', 'admin'], true);

        $this->get(route('user.edit.info', $target))->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('user_to_edit.email', $canViewContacts ? 'private@example.test' : null)
                ->where('user_to_edit.phone_number', $canViewContacts ? '0123456789' : null)
                ->where('user_to_edit.salary_description', $canViewTerms ? 'Confidential' : null)
                ->where('user_to_edit.salary_per_hour', $canViewTerms ? 123 : null)
                ->where('user_to_edit.employStart', $canViewTerms ? '2026-01-01' : null)
                ->where('user_to_edit.employEnd', $canViewTerms ? '2026-12-31' : null)
        );
        $termsResponse = $this->get(route('user.edit.terms', $target));
        if ($canViewTerms) {
            $termsResponse->assertOk()->assertInertia(
                fn (AssertableInertia $page) => $page->where('user_to_edit.salary_description', 'Confidential')
            );
        } else {
            $termsResponse->assertForbidden();
        }
    }

    public static function profileAccess(): iterable
    {
        foreach (['none', 'self', 'private', 'manager', 'admin'] as $access) {
            yield $access => [$access];
        }
    }

    #[Test]
    public function public_profile_contacts_remain_visible(): void
    {
        $this->actingAs(User::factory()->create());
        $target = User::factory()->create(['email_private' => false, 'phone_private' => false, 'phone_number' => '01234']);
        $this->get(route('user.edit.info', $target))->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('user_to_edit.email', $target->email)->where('user_to_edit.phone_number', '01234')
        );
    }

    #[Test]
    #[DataProvider('allowedAccess')]
    public function employment_changes_require_worker_management(bool $allowed): void
    {
        $target = User::factory()->create(['temporary' => false, 'employEnd' => null]);
        if ($allowed) {
            $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        } else {
            $this->actingAs($target);
        }
        $response = $this->patch(route('update.user.temporary', $target), [
            'temporary' => true, 'employStart' => '2026-01-01', 'employEnd' => '2026-12-31',
        ]);
        if ($allowed) {
            $response->assertSuccessful();
            $this->assertDatabaseHas('users', ['id' => $target->id, 'temporary' => true, 'employEnd' => '2026-12-31']);
        } else {
            $response->assertForbidden();
            $this->assertDatabaseHas('users', ['id' => $target->id, 'temporary' => false, 'employEnd' => null]);
        }
    }

    public static function allowedAccess(): iterable
    {
        yield 'denied' => [false];
        yield 'allowed' => [true];
    }

    #[Test]
    #[DataProvider('taskAccess')]
    public function task_actions_require_checklist_access(string $operation, bool $allowed): void
    {
        $actor = User::factory()->create();
        $owner = $allowed ? $actor : User::factory()->create();
        $checklist = Checklist::factory()->create(['project_id' => null, 'user_id' => $owner->id, 'private' => true]);
        $task = Task::factory()->create(['checklist_id' => $checklist->id, 'name' => 'Original', 'done' => false, 'order' => 1]);
        $this->actingAs($actor);
        $before = $task->fresh()->getAttributes();
        $response = match ($operation) {
            'create' => $this->post(route('tasks.store'), [
                'name' => 'New task', 'checklist_id' => $checklist->id, 'users' => [],
                'description' => '', 'deadlineDate' => '2026-12-01',
            ]),
            'edit' => $this->get('/tasks/'.$task->id.'/edit'),
            'update' => $this->patch(route('tasks.update', $task), [
                'id' => $task->id, 'name' => 'Changed', 'checklist_id' => $checklist->id, 'users' => [],
            ]),
            'delete' => $this->delete(route('tasks.destroy', $task)),
            'done' => $this->patch(route('tasks.done', $task)),
            'order' => $this->put(route('tasks.order'), ['checklistTasks' => [['id' => $task->id, 'order' => 5]]]),
        };
        if (!$allowed) {
            $response->assertForbidden();
            $this->assertSame($before, $task->fresh()->getAttributes());
            $this->assertSame(1, $checklist->tasks()->count());
            return;
        }
        if ($operation === 'edit') {
            $response->assertOk();
        } else {
            $response->assertRedirect();
            match ($operation) {
                'create' => $this->assertSame(2, $checklist->tasks()->count()),
                'update' => $this->assertSame('Changed', $task->fresh()->name),
                'delete' => $this->assertDatabaseMissing('tasks', ['id' => $task->id]),
                'done' => $this->assertTrue($task->fresh()->done),
                'order' => $this->assertSame(5, $task->fresh()->order),
            };
        }
    }

    public static function taskAccess(): iterable
    {
        foreach (['create', 'edit', 'update', 'delete', 'done', 'order'] as $operation) {
            yield "$operation denied" => [$operation, false];
            yield "$operation allowed" => [$operation, true];
        }
    }

    #[Test]
    public function reordering_mixed_checklists_does_not_partially_modify_allowed_tasks(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $own = Checklist::factory()->create(['project_id' => null, 'user_id' => $actor->id]);
        $foreign = Checklist::factory()->create(['project_id' => null, 'user_id' => User::factory()]);
        $first = Task::factory()->create(['checklist_id' => $own->id, 'order' => 1]);
        $second = Task::factory()->create(['checklist_id' => $foreign->id, 'order' => 2]);
        $this->put(route('tasks.order'), ['checklistTasks' => [
            ['id' => $first->id, 'order' => 5], ['id' => $second->id, 'order' => 6],
        ]])->assertForbidden();
        $this->assertSame(1, $first->fresh()->order);
        $this->assertSame(2, $second->fresh()->order);
    }

    #[Test]
    #[DataProvider('moveAccess')]
    public function moving_tasks_requires_access_to_both_checklists(bool $sourceAllowed, bool $targetAllowed): void
    {
        $actor = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($actor);
        $source = Checklist::factory()->create(['project_id' => null, 'user_id' => $sourceAllowed ? $actor->id : $other->id]);
        $target = Checklist::factory()->create(['project_id' => null, 'user_id' => $targetAllowed ? $actor->id : $other->id]);
        $task = Task::factory()->create(['checklist_id' => $source->id]);
        $response = $this->patch(route('checklists.change.task', [$target, $task]));
        if ($sourceAllowed && $targetAllowed) {
            $response->assertSuccessful();
            $this->assertSame($target->id, $task->fresh()->checklist_id);
        } else {
            $response->assertForbidden();
            $this->assertSame($source->id, $task->fresh()->checklist_id);
        }
    }

    public static function moveAccess(): iterable
    {
        yield 'foreign source' => [false, true];
        yield 'foreign destination' => [true, false];
        yield 'both allowed' => [true, true];
    }

    #[Test]
    #[DataProvider('allowedAccess')]
    public function money_source_categories_require_settings_permission(bool $allowed): void
    {
        if ($allowed) {
            $this->actingAsUserWith('change money source settings');
        } else {
            $this->actingAsUserWith('view edit add money_sources');
        }
        $category = MoneySourceCategory::create(['name' => 'Existing']);
        $source = MoneySource::factory()->create();
        $source->categories()->attach($category);
        $store = $this->post(route('money_source_categories.store'), ['name' => 'New category']);
        $delete = $this->delete(route('money_source_categories.destroy', $category));
        if ($allowed) {
            $store->assertRedirect();
            $delete->assertRedirect();
            $this->assertDatabaseHas('money_source_categories', ['name' => 'New category']);
            $this->assertDatabaseMissing('money_source_categories', ['id' => $category->id]);
            $this->assertSame(0, $source->categories()->count());
        } else {
            $store->assertForbidden();
            $delete->assertForbidden();
            $this->assertDatabaseMissing('money_source_categories', ['name' => 'New category']);
            $this->assertSame(1, $source->categories()->count());
        }
    }

    #[Test]
    #[DataProvider('allowedAccess')]
    public function money_source_reminders_require_access_to_the_source(bool $allowed): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $source = MoneySource::factory()->create();
        if ($allowed) {
            $source->users()->attach($actor, ['write_access' => true]);
        }
        $response = $this->post(route('money_source.reminder.store', $source), [
            'expirationReminders' => [['days' => 7]], 'thresholdReminders' => [['threshold' => 50]],
        ]);
        if ($allowed) {
            $response->assertSuccessful();
            $this->assertSame(2, $source->reminder()->count());
        } else {
            $response->assertForbidden();
            $this->assertSame(0, $source->reminder()->count());
        }
    }

    #[Test]
    #[DataProvider('shiftPresetAccess')]
    public function shift_preset_insertion_obeys_the_existing_settings_permissions(bool $granular, string $access, bool $allowed): void
    {
        $settings = app(ShiftSettings::class);
        $settings->granular_permissions_enabled = $granular;
        $settings->save();
        $actor = User::factory()->create();
        $this->actingAs($actor);
        if ($access === 'admin') {
            $this->actingAsAdmin($actor);
        } elseif ($access !== 'none') {
            $permissions = [PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT->value];
            if ($access === 'editor') {
                $permissions[] = PermissionEnum::SHIFT_SETTINGS_SHIFT_TEMPLATES_EDIT->value;
            }
            $this->actingAsUserWith($permissions, $actor);
        }
        $preset = ShiftPreset::factory()->create();
        $craft = Craft::factory()->create();
        $response = $this->post(route('shift.preset.store', $preset), [
            'start' => '10:00', 'end' => '16:00', 'break_minutes' => 30,
            'craft_id' => $craft->id, 'description' => 'New preset shift', 'presetShiftsQualifications' => [],
        ]);
        if ($allowed) {
            $response->assertSuccessful();
            $this->assertDatabaseHas('preset_shifts', ['shift_preset_id' => $preset->id]);
        } else {
            $response->assertForbidden();
            $this->assertDatabaseMissing('preset_shifts', ['shift_preset_id' => $preset->id]);
        }
    }

    public static function shiftPresetAccess(): iterable
    {
        yield 'unprivileged' => [false, 'none', false];
        yield 'simple master' => [false, 'master', true];
        yield 'granular master only' => [true, 'master', false];
        yield 'granular editor' => [true, 'editor', true];
        yield 'admin' => [true, 'admin', true];
    }

    #[Test]
    #[DataProvider('workTimeRequestAccess')]
    public function work_time_requests_bind_the_worker_author_and_craft(string $scenario, bool $allowed): void
    {
        $actor = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($actor);
        $craft = Craft::factory()->create(['assignable_by_all' => false]);
        $shift = Shift::factory()->create(['craft_id' => $craft->id]);
        $target = $scenario === 'foreign worker' ? $other : $actor;
        if ($scenario !== 'unassigned') {
            $shift->users()->attach($target->id, [
                'shift_qualification_id' => ShiftQualification::factory()->create()->id,
                'deleted_at' => $scenario === 'deleted assignment' ? now() : null,
            ]);
        }
        $payload = [
            'request_start_time' => '10:00', 'request_end_time' => '16:00',
            'shift_id' => $shift->id, 'craft_id' => $craft->id, 'request_comment' => 'Adjustment',
            'user_id' => $target->id, 'requested_by' => $other->id,
        ];
        if ($scenario === 'wrong craft') {
            $payload['craft_id'] = Craft::factory()->create()->id;
        } elseif ($scenario === 'no supplied author') {
            unset($payload['requested_by']);
        } elseif ($scenario === 'overnight') {
            $payload['request_start_time'] = '22:00';
            $payload['request_end_time'] = '02:00';
        }
        $response = $this->post(route('shifts.requestWorkTimeChange'), $payload);
        if ($allowed) {
            $response->assertSuccessful();
            $this->assertDatabaseHas('work_time_change_requests', [
                'user_id' => $actor->id, 'requested_by' => $actor->id,
                'shift_id' => $shift->id, 'craft_id' => $craft->id, 'status' => 'pending',
            ]);
        } else {
            $response->assertForbidden();
            $this->assertDatabaseMissing('work_time_change_requests', ['shift_id' => $shift->id]);
        }
    }

    public static function workTimeRequestAccess(): iterable
    {
        yield 'foreign worker' => ['foreign worker', false];
        yield 'unassigned' => ['unassigned', false];
        yield 'deleted assignment' => ['deleted assignment', false];
        yield 'wrong craft' => ['wrong craft', false];
        yield 'spoofed author replaced' => ['spoofed author', true];
        yield 'no supplied author' => ['no supplied author', true];
        yield 'overnight' => ['overnight', true];
    }

    #[Test]
    public function saving_a_profile_never_overwrites_hidden_contact_data(): void
    {
        $target = User::factory()->create([
            'email' => 'private@example.test', 'email_private' => true,
            'phone_number' => '0123456789', 'phone_private' => true,
        ]);
        // Ohne "Private Kontaktdaten einsehen" liefert UserShowResource null – das Formular schickt es zurück.
        $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);

        $this->patch(route('user.update', $target), [
            'first_name' => 'Neu', 'last_name' => $target->last_name,
            'position' => 'Bühne', 'business' => 'Haus',
            'email' => null, 'phone_number' => null, 'departments' => [],
        ])->assertRedirect();

        $target->refresh();
        $this->assertSame('Neu', $target->first_name);
        $this->assertSame('private@example.test', $target->email);
        $this->assertSame('0123456789', $target->phone_number);
    }

    #[Test]
    public function checklist_payload_mirrors_the_update_right(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $checklist = Checklist::factory()->create(['project_id' => null, 'user_id' => $owner->id, 'private' => true]);
        Task::factory()->create(['checklist_id' => $checklist->id, 'order' => 1]);

        $resolveAs = static fn (User $user): bool => ChecklistIndexResource::make($checklist->fresh())
            ->resolve(request()->setUserResolver(static fn () => $user))['can_update'];

        $this->assertTrue($resolveAs($owner));
        $this->assertFalse($resolveAs($stranger));
    }
}
