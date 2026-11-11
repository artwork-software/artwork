<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Konzept C2 §3: Ein Haus-Admin darf keinen ein Jahr gueltigen Kunden-Key mit ai:*-Scopes
 * erzeugen — der haenge an keiner Session und koennte den KI-Dienst ungebunden ansprechen.
 */
final class AiScopesExcludedFromPersonalAccessTokensTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app(ClientRepository::class)
            ->createPersonalAccessGrantClient('PAT client', config('auth.guards.api.provider'));
    }

    #[Test]
    public function the_ai_scopes_are_registered_at_all(): void
    {
        foreach (AiScopes::all() as $scope) {
            $this->assertTrue(Passport::hasScope($scope), "Scope {$scope} fehlt in tokensCan()");
        }
    }

    #[Test]
    public function a_personal_access_token_can_not_be_created_with_an_ai_scope(): void
    {
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        foreach (AiScopes::all() as $scope) {
            $this->post(route('api-management.store'), ['name' => 'key', 'scopes' => [$scope]])
                ->assertSessionHasErrors('scopes.0');
        }
    }

    #[Test]
    public function a_personal_access_token_can_still_be_created_with_a_machine_scope(): void
    {
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        $before = Passport::token()->newQuery()->count();

        $this->post(route('api-management.store'), ['name' => 'key', 'scopes' => ['inventory:read']])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame($before + 1, Passport::token()->newQuery()->count());
    }

    #[Test]
    public function the_settings_screen_does_not_offer_the_ai_scopes(): void
    {
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        $response = $this->get(route('tool.interfaces'));
        $response->assertOk();

        $offered = collect($response->viewData('page')['props']['availableScopes'])->pluck('id')->all();

        $this->assertContains('inventory:read', $offered);
        foreach (AiScopes::all() as $scope) {
            $this->assertNotContains($scope, $offered);
        }
    }
}
