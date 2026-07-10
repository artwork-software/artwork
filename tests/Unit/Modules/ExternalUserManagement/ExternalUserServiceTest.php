<?php

namespace Tests\Unit\Modules\ExternalUserManagement;

use Artwork\Modules\ExternalUserManagement\Models\ExternalUser;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserGroupMapping;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\ExternalUserManagement\Service\ExternalUserGroupMappingService;
use Artwork\Modules\ExternalUserManagement\Service\ExternalUserService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class ExternalUserServiceTest extends TestCase
{
    #[Test]
    public function identifiers_are_resolved_within_their_external_source(): void
    {
        $firstSource = $this->source('First');
        $secondSource = $this->source('Second');
        $firstUser = User::factory()->create([
            'email' => 'first@example.com',
            'ad_managed' => true,
            'ad_identifier' => 'shared-identifier',
        ]);

        ExternalUser::query()->create([
            'source_id' => $firstSource->id,
            'user_id' => $firstUser->id,
            'identification' => 'shared-identifier',
        ]);

        $secondUser = app(ExternalUserService::class)->findOrCreateUser(
            $secondSource,
            [
                'email' => 'second@example.com',
                'first_name' => 'Second',
                'last_name' => 'User',
            ],
            'shared-identifier'
        );

        $this->assertFalse($firstUser->is($secondUser));
        $this->assertSame('second@example.com', $secondUser->email);
    }

    #[Test]
    public function an_external_identity_cannot_take_over_a_local_user_by_email(): void
    {
        $source = $this->source('Identity Provider');
        User::factory()->create([
            'email' => 'local@example.com',
            'ad_managed' => false,
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(ExternalUserService::class)->findOrCreateUser(
            $source,
            [
                'email' => 'local@example.com',
                'first_name' => 'External',
                'last_name' => 'Identity',
            ],
            'external-subject'
        );
    }

    #[Test]
    public function mapped_roles_are_removed_when_group_membership_is_revoked(): void
    {
        $source = $this->source('LDAP');
        $user = User::factory()->create(['ad_managed' => true]);
        $role = Role::query()->create(['name' => 'external manager', 'guard_name' => 'web']);
        $user->assignRole($role);

        ExternalUser::query()->create([
            'source_id' => $source->id,
            'user_id' => $user->id,
            'identification' => 'external-user',
        ]);
        ExternalUserGroupMapping::query()->create([
            'source_id' => $source->id,
            'ad_group_dn' => 'CN=External Managers',
            'ad_group_name' => 'External Managers',
            'role_ids' => [$role->id],
        ]);

        app(ExternalUserService::class)->syncUserGroups(
            $source,
            $user,
            [],
            app(ExternalUserGroupMappingService::class)
        );

        $this->assertFalse($user->fresh()->hasRole($role));
    }

    #[Test]
    public function a_role_is_kept_when_any_mapped_group_still_grants_it(): void
    {
        $source = $this->source('LDAP');
        $user = User::factory()->create(['ad_managed' => true]);
        $role = Role::query()->create(['name' => 'shared external role', 'guard_name' => 'web']);

        foreach (['CN=First Group', 'CN=Second Group'] as $groupDn) {
            ExternalUserGroupMapping::query()->create([
                'source_id' => $source->id,
                'ad_group_dn' => $groupDn,
                'ad_group_name' => $groupDn,
                'role_ids' => [$role->id],
            ]);
        }

        app(ExternalUserService::class)->syncUserGroups(
            $source,
            $user,
            ['CN=Second Group'],
            app(ExternalUserGroupMappingService::class)
        );

        $this->assertTrue($user->fresh()->hasRole($role));
    }

    #[Test]
    public function a_soft_deleted_external_identity_is_restored_on_reconnection(): void
    {
        $source = $this->source('Reconnect');
        $externalUser = ExternalUser::query()->create([
            'source_id' => $source->id,
            'identification' => 'returning-user',
        ]);
        $externalUser->delete();

        $restored = app(ExternalUserService::class)->findOrCreateExternalUser(
            $source,
            'returning-user',
            ['email' => 'returning@example.com']
        );

        $this->assertTrue($restored->is($externalUser));
        $this->assertFalse($restored->trashed());
    }

    #[Test]
    public function source_secrets_are_encrypted_at_rest_and_omitted_from_serialization(): void
    {
        $source = ExternalUserSource::query()->create([
            'name' => 'Encrypted source',
            'active' => true,
            'type' => 'identity_provider',
            'config' => [
                'discovery_url' => 'https://idp.example.com/.well-known/openid-configuration',
                'client_id' => 'artwork',
                'client_secret' => 'top-secret',
                'bind_password' => 'ldap-secret',
            ],
        ]);

        $rawConfig = DB::table('external_user_sources')->where('id', $source->id)->value('config');

        $this->assertStringNotContainsString('top-secret', $rawConfig);
        $this->assertStringNotContainsString('ldap-secret', $rawConfig);
        $this->assertSame('top-secret', $source->fresh()->config['client_secret']);
        $this->assertArrayNotHasKey('client_secret', $source->toArray()['config']);
        $this->assertArrayNotHasKey('bind_password', $source->toArray()['config']);
    }

    private function source(string $name): ExternalUserSource
    {
        return ExternalUserSource::query()->create([
            'name' => $name,
            'active' => true,
            'type' => 'identity_provider',
            'config' => [
                'discovery_url' => 'https://idp.example.com/.well-known/openid-configuration',
                'client_id' => 'artwork',
                'client_secret' => 'secret',
            ],
        ]);
    }
}
