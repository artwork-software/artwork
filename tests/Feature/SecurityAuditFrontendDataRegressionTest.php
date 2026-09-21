<?php

namespace Tests\Feature;

use Artwork\Modules\ExternalAccess\Settings\ExternalAccessSettings;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\User\Http\Resources\UserShowResource;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsRole;
use Tighten\Ziggy\Ziggy;

/**
 * Kein Datenabfluss ins Frontend: Gehaltsfelder in auth.user, Ziggy-Routenkarte für Externe, Chat-Keypair, externe Font-Abrufe.
 */
final class SecurityAuditFrontendDataRegressionTest extends FeatureTestCase
{
    use ActsAsRole;

    private const SALARY_FIELDS = ['salary_per_hour', 'salary_description', 'weekly_working_hours'];

    protected function setUp(): void
    {
        parent::setUp();

        $settings = app(GeneralSettings::class);
        $settings->setup_finished = true;
        $settings->save();
    }

    #[Test]
    public function auth_user_prop_does_not_expose_salary_fields(): void
    {
        $admin = $this->actingAsAdmin();
        $admin->forceFill([
            'salary_per_hour' => 42,
            'salary_description' => 'Tarif X',
            'weekly_working_hours' => 39,
        ])->save();

        $response = $this->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(function (AssertableInertia $page): void {
            $page->has('auth.user.id');
            foreach (self::SALARY_FIELDS as $field) {
                $page->missing('auth.user.' . $field);
            }
        });
    }

    #[Test]
    public function user_to_array_hides_salary_fields_but_user_show_resource_still_delivers_them(): void
    {
        $admin = $this->actingAsAdmin();
        $admin->forceFill([
            'salary_per_hour' => 42,
            'salary_description' => 'Tarif X',
        ])->save();

        $array = $admin->fresh()->toArray();
        foreach (self::SALARY_FIELDS as $field) {
            $this->assertArrayNotHasKey($field, $array, $field . ' darf nicht in toArray() erscheinen');
        }

        // Legitimer Pfad: UserShowResource greift direkt auf das Attribut zu.
        $manager = $this->actingAsUserWith(PermissionEnum::MA_MANAGER->value);
        $request = request();
        $request->setUserResolver(fn () => $manager);
        $resource = (new UserShowResource($admin->fresh()))->resolve($request);
        $this->assertSame(42.0, (float) $resource['salary_per_hour']);
        $this->assertSame('Tarif X', $resource['salary_description']);
    }

    #[Test]
    public function external_ziggy_group_contains_only_external_routes(): void
    {
        $names = array_keys((new Ziggy('external'))->toArray()['routes']);

        $this->assertNotEmpty($names);
        $this->assertContains('external.login.form', $names);
        $this->assertContains('external.logout', $names);
        $this->assertContains('external.dashboard', $names);

        foreach ($names as $name) {
            $this->assertStringStartsWith('external.', $name, 'Fremde Route in externer Ziggy-Gruppe: ' . $name);
        }

        $forbidden = [
            'user.edit.permissions',
            'users',
            'tool.external-user-management',
            'settings.external-access.index',
            'dashboard',
            'horizon.index',
        ];
        foreach ($forbidden as $routeName) {
            $this->assertNotContains($routeName, $names);
        }
    }

    #[Test]
    public function external_layout_ships_only_the_external_route_group(): void
    {
        $settings = app(ExternalAccessSettings::class);
        $settings->enabled = true;
        $settings->save();

        $response = $this->get('/external/login');

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('"external.login.form"', $html);
        $this->assertStringNotContainsString('"user.edit.permissions"', $html);
        $this->assertStringNotContainsString('"tool.external-user-management"', $html);
        $this->assertStringNotContainsString('"projects.index"', $html);
        $this->assertStringNotContainsString('"horizon.index"', $html);
    }

    #[Test]
    public function internal_ziggy_route_map_excludes_tooling_routes(): void
    {
        $names = array_keys((new Ziggy())->toArray()['routes']);

        $this->assertContains('dashboard', $names);
        $this->assertContains('external.login.form', $names);

        $tooling = array_filter(
            $names,
            fn (string $name) => preg_match('/^(telescope|horizon|debugbar|ignition|sanctum|passport)/', $name) === 1
        );
        $this->assertSame([], array_values($tooling), 'Tooling-Routen dürfen nicht in der Routenkarte landen');

        $this->actingAsAdmin();
        $html = $this->get('/dashboard')->assertOk()->getContent();
        $this->assertStringNotContainsString('"telescope', $html);
        $this->assertStringNotContainsString('"horizon.', $html);
        $this->assertStringNotContainsString('"passport.', $html);
    }

    #[Test]
    public function chat_keypair_endpoint_and_column_are_gone(): void
    {
        $this->assertFalse(Route::has('keypair.store'));
        $this->assertFalse(Schema::hasColumn('users', 'chat_public_key'));
        $this->assertNotContains('chat_public_key', (new User())->getFillable());
    }

    #[Test]
    public function mail_and_pdf_templates_do_not_load_external_fonts(): void
    {
        $templates = [
            resource_path('views/vendor/mail/html/layout.blade.php'),
            resource_path('views/vendor/mail/html/button.blade.php'),
            resource_path('emails/layout.html'),
            resource_path('emails/layout.mjml'),
            resource_path('views/pdf/artist-residency-per-diem.blade.php'),
            resource_path('views/pdf/artist-residency-per-diem-standalone.blade.php'),
        ];

        foreach ($templates as $template) {
            $this->assertFileExists($template);
            $content = file_get_contents($template);
            $this->assertStringNotContainsString('fonts.googleapis', $content, basename($template));
            $this->assertStringNotContainsString('fonts.bunny', $content, basename($template));
            $this->assertStringNotContainsString('fonts.gstatic', $content, basename($template));
        }

        app('view')->addNamespace('mail', resource_path('views/vendor/mail'));
        $rendered = view('mail::html.layout', ['slot' => 'x'])->render();
        $this->assertStringNotContainsString('fonts.googleapis', $rendered);
    }
}
