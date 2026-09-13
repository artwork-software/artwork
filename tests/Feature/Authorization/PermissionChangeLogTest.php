<?php

namespace Tests\Feature\Authorization;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Services\PermissionChangeLogService;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\FeatureTestCase;

final class PermissionChangeLogTest extends FeatureTestCase
{
    #[Test]
    public function permission_changes_are_logged_with_diff_and_causer(): void
    {
        $admin = $this->actingAsAdmin();
        $target = User::factory()->create();
        $target->givePermissionTo(PermissionEnum::PROJECT_VIEW->value);

        $this->patch(route('user.update.permissions-and-roles', $target), [
            'permissions' => [PermissionEnum::WRITE_PROJECTS->value],
            'roles' => [],
            'source' => 'preset',
        ])->assertRedirect();

        $entry = Activity::query()
            ->where('log_name', PermissionChangeLogService::LOG_NAME)
            ->where('subject_id', $target->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame($admin->id, $entry->causer_id);
        // Implikation: "Schreibrechte" enthält "Leserechte" → nur das neue Recht ist "added", nichts "removed"
        $this->assertSame([PermissionEnum::WRITE_PROJECTS->value], $entry->properties['added']);
        $this->assertSame([], $entry->properties['removed']);
        $this->assertSame('preset', $entry->properties['source']);

        $history = app(PermissionChangeLogService::class)->historyFor($target->fresh());
        $this->assertCount(1, $history);
        $this->assertSame($admin->id, $history[0]['causer']['id']);
    }

    #[Test]
    public function unchanged_saves_do_not_create_log_entries(): void
    {
        $this->actingAsAdmin();
        $target = User::factory()->create();
        $target->givePermissionTo(PermissionEnum::PROJECT_VIEW->value);

        $this->patch(route('user.update.permissions-and-roles', $target), [
            'permissions' => [PermissionEnum::PROJECT_VIEW->value],
            'roles' => [],
        ])->assertRedirect();

        $this->assertSame(0, Activity::query()->where('log_name', PermissionChangeLogService::LOG_NAME)->where('subject_id', $target->id)->count());
    }

    #[Test]
    public function permissions_page_exposes_history_usage_and_colleagues(): void
    {
        $this->actingAsAdmin();
        $target = User::factory()->create();
        User::factory()->create();

        $this->get(route('user.edit.permissions', $target))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('permission_history')
                ->has('colleagues')
                ->has('catalog.usage'));
    }
}
