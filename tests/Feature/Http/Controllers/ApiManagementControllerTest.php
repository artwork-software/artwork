<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Core\Api\Models\ApiLog;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ApiManagementControllerTest extends FeatureTestCase
{
    #[Test]
    public function plaintext_token_storage_is_gone(): void
    {
        $this->assertFalse(
            Schema::hasTable('api_access_tokens'),
            'api_access_tokens held a plaintext copy of the JWT and must no longer exist.'
        );
        $this->assertFalse(Schema::hasColumn('api_log', 'api_key'));
        $this->assertFalse(Schema::hasColumn('api_log', 'payload'));
        $this->assertTrue(Schema::hasColumn('api_log', 'passport_token_id'));
    }

    #[Test]
    public function user_without_tool_settings_permission_cannot_create_token(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('api-management.store'), ['name' => 'Shop'])
            ->assertForbidden();
    }

    #[Test]
    public function user_with_tool_settings_permission_can_create_token(): void
    {
        $this->preparePassportClient();
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        // Vor der Reparatur warf dieser Aufruf einen ArgumentCountError: der Direktaufruf der
        // PersonalAccessTokenFactory ließ den in Passport 13 hinzugekommenen Provider-Parameter aus.
        $response = $this->post(route('api-management.store'), ['name' => 'Ticketshop']);

        $response->assertRedirect();
        $response->assertSessionHas('plainTextToken');
        $this->assertDatabaseHas('oauth_access_tokens', ['name' => 'Ticketshop', 'revoked' => false]);
    }

    #[Test]
    public function created_token_is_never_persisted_in_plaintext(): void
    {
        $this->preparePassportClient();
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        $plainTextToken = $this->post(route('api-management.store'), ['name' => 'Ticketshop'])
            ->getSession()
            ->get('plainTextToken');

        $this->assertIsString($plainTextToken);
        $this->assertNotEmpty($plainTextToken);

        // Das JWT darf ausschließlich in der Flash-Nachricht existieren, in keiner Tabelle.
        $this->assertDatabaseMissing('oauth_access_tokens', ['id' => $plainTextToken]);
    }

    #[Test]
    public function token_can_be_revoked(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);
        $token = $this->makeTokenRecord($user);

        // Vor der Reparatur rief der Controller TokenRepository::revokeAccessToken() auf — eine
        // Methode, die es in Passport 13 nicht mehr gibt.
        $this->delete(route('api-management.destroy', $token->getKey()))
            ->assertRedirect();

        $this->assertDatabaseHas('oauth_access_tokens', [
            'id' => $token->getKey(),
            'revoked' => true,
        ]);
    }

    #[Test]
    public function user_without_tool_settings_permission_cannot_revoke_token(): void
    {
        $owner = User::factory()->create();
        $token = $this->makeTokenRecord($owner);

        $this->actingAs(User::factory()->create());

        $this->delete(route('api-management.destroy', $token->getKey()))
            ->assertForbidden();

        $this->assertDatabaseHas('oauth_access_tokens', [
            'id' => $token->getKey(),
            'revoked' => false,
        ]);
    }

    #[Test]
    public function revoking_an_unknown_token_reports_an_error_instead_of_failing(): void
    {
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        $this->delete(route('api-management.destroy', 'does-not-exist'))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    #[Test]
    public function token_logs_are_scoped_to_the_requested_token(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);
        $token = $this->makeTokenRecord($user);
        $otherToken = $this->makeTokenRecord($user);

        ApiLog::create([
            'passport_token_id' => $token->getKey(),
            'url' => 'https://example.test/api/v1/inventory',
            'method' => 'GET',
            'ip' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'response_status' => 200,
            'duration_ms' => 12,
        ]);
        ApiLog::create([
            'passport_token_id' => $otherToken->getKey(),
            'url' => 'https://example.test/api/v1/inventory',
            'method' => 'GET',
            'ip' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'response_status' => 200,
            'duration_ms' => 34,
        ]);

        $response = $this->getJson(route('tool.interfaces.api.logs', $token->getKey()));

        $response->assertOk();
        $response->assertJsonPath('logs.total', 1);
        $response->assertJsonPath('logs.data.0.passport_token_id', $token->getKey());
    }

    private function makeTokenRecord(User $user): Token
    {
        $token = new Token();
        $token->forceFill([
            'id' => 'test-token-' . bin2hex(random_bytes(8)),
            'user_id' => $user->getKey(),
            'client_id' => 1,
            'name' => 'Test token',
            'scopes' => [],
            'revoked' => false,
            'expires_at' => now()->addYear(),
        ])->save();

        return $token;
    }

    /**
     * Das Anlegen eines Personal-Access-Tokens braucht Signaturschlüssel und einen passenden Client.
     * Die Schlüssel werden nur erzeugt, wenn sie fehlen — ein --force würde auf Entwicklerrechnern
     * bestehende Schlüssel überschreiben und alle vorhandenen Tokens entwerten.
     */
    private function preparePassportClient(): void
    {
        if (!file_exists(Passport::keyPath('oauth-private.key'))) {
            $this->artisan('passport:keys');
        }

        app(ClientRepository::class)->createPersonalAccessGrantClient(
            'Test Personal Access Client',
            'users'
        );
    }
}
