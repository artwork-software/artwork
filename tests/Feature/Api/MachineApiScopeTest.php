<?php

namespace Tests\Feature\Api;

use Artwork\Modules\User\Models\User;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Scope-Durchsetzung der versionierten Maschinen-API.
 *
 * Passport::actingAs() setzt die Scopes so, wie sie sonst aus dem signierten JWT kämen — genau der
 * Weg, den auch CheckToken auswertet.
 */
final class MachineApiScopeTest extends FeatureTestCase
{
    #[Test]
    public function unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/v1/inventory')->assertUnauthorized();
    }

    #[Test]
    public function token_with_matching_scope_is_allowed(): void
    {
        Passport::actingAs(User::factory()->create(), ['inventory:read']);

        $this->getJson('/api/v1/inventory')->assertOk();
    }

    #[Test]
    public function token_with_a_different_scope_is_rejected(): void
    {
        Passport::actingAs(User::factory()->create(), ['ticketing:read']);

        $this->getJson('/api/v1/inventory')->assertForbidden();
    }

    #[Test]
    public function token_without_any_scope_is_rejected(): void
    {
        // Das ist der Zustand jedes vor der Scope-Einführung ausgegebenen Tokens. Solche Tokens
        // lassen sich nicht nachrüsten — die Scopes stehen im signierten JWT — und müssen deshalb
        // neu erstellt werden. Dieser Test hält die Entscheidung fest.
        Passport::actingAs(User::factory()->create(), []);

        $this->getJson('/api/v1/inventory')->assertForbidden();
    }

    #[Test]
    public function wildcard_scope_is_accepted(): void
    {
        Passport::actingAs(User::factory()->create(), ['*']);

        $this->getJson('/api/v1/inventory')->assertOk();
    }

    #[Test]
    public function all_registered_scopes_are_available(): void
    {
        // Teilmengen-Prüfung statt exakter Gleichheit: Vendor-Pakete registrieren eigene Scopes
        // (laravel/mcp etwa "mcp:use"), die hier nicht mitgepflegt werden sollen.
        foreach (['inventory:read', 'ticketing:read', 'ticketing:write'] as $scopeId) {
            $this->assertContains($scopeId, Passport::scopeIds());
        }
    }
}
