<?php

namespace Tests\Feature\AppApi;

use Artwork\Modules\ModuleSettings\Models\ModuleSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Modul-Schalter gelten auch für die App-API (EnsureAppModuleEnabled): abgeschaltete Module
 * antworten 403, Admins dürfen sie wie im Web weiter nutzen.
 */
final class AppModuleSettingsTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function moduleRoutes(): array
    {
        return [
            'Dienstplan' => ['shift_plan', 'app.v1.shift-plan'],
            'Schichtliste' => ['shift_plan', 'app.v1.shift-list'],
            'Raumbelegung' => ['room_assignment', 'app.v1.calendar'],
            'Projektliste' => ['projects', 'app.v1.projects'],
        ];
    }

    #[Test]
    #[DataProvider('moduleRoutes')]
    public function disabled_modules_are_rejected_for_regular_users(string $module, string $routeName): void
    {
        $this->actingAsApiUserWith([
            PermissionEnum::CAN_VIEW_OWN_ROSTER->value,
            PermissionEnum::VIEW_SHIFT_PLAN->value,
            PermissionEnum::PROJECT_VIEW->value,
        ]);

        $this->getJson(route($routeName))->assertOk();

        $this->disableModule($module);

        $this->getJson(route($routeName))->assertForbidden();
    }

    #[Test]
    #[DataProvider('moduleRoutes')]
    public function admins_keep_access_to_disabled_modules(string $module, string $routeName): void
    {
        $this->disableModule($module);
        Passport::actingAs($this->adminUser(), ['app']);

        $this->getJson(route($routeName))->assertOk();
    }

    #[Test]
    public function the_projects_switch_covers_every_project_endpoint(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user->id);
        Passport::actingAs($user, ['app']);

        $this->getJson(route('app.v1.projects.show', $project))->assertOk();

        $this->disableModule('projects');

        $this->getJson(route('app.v1.projects.show', $project))->assertForbidden();
        $this->postJson(route('app.v1.projects.comments.store', $project), ['text' => 'x'])->assertForbidden();
        $this->assertSame(0, $project->comments()->count());
    }

    private function disableModule(string $module): void
    {
        $settings = app(ModuleSettings::class);
        $settings->{$module} = false;
        $settings->save();
    }
}
