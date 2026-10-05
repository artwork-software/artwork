<?php

namespace Tests\Feature\ExternalUserManagement;

use Artwork\Core\Mail\MailService;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserGroupMapping;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\ExternalUserManagement\Service\ExternalUserSyncService;
use Artwork\Modules\ExternalUserManagement\Service\LdapService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Feature\FeatureTestCase;

/**
 * Der 30-Minuten-Sync gegen LDAP: Provisionierung inkl. Grundausstattung, Rechte aus
 * Gruppen-Mappings (vergeben UND entziehen), Willkommens-Mail genau einmal, robuste
 * Fehlerbehandlung je Eintrag. Das Verzeichnis selbst ist über LdapService gemockt.
 */
final class ExternalUserSyncServiceTest extends FeatureTestCase
{
    private const GROUP_DN = 'cn=technik,ou=groups,dc=example,dc=test';

    private ExternalUserSource $source;
    private Permission $permission;
    private int $welcomeMails = 0;

    protected function setUp(): void
    {
        parent::setUp();
        // MailService erwartet den echten MailManager (verträgt kein Mail::fake) – Aufrufe zählen
        $this->mock(MailService::class, function ($mock): void {
            $mock->shouldReceive('sendExternalUserImported')->andReturnUsing(function (): void {
                $this->welcomeMails++;
            });
        });

        $this->source = ExternalUserSource::query()->create([
            'name' => 'LDAP',
            'active' => true,
            'type' => 'ldap',
            'config' => ['host' => 'ldap.example.test'],
        ]);
        $this->permission = Permission::findOrCreate(PermissionEnum::VIEW_SHIFT_PLAN->value, 'web');
        ExternalUserGroupMapping::query()->create([
            'source_id' => $this->source->id,
            'ad_group_dn' => self::GROUP_DN,
            'ad_group_name' => 'Technik',
            'permission_ids' => [$this->permission->id],
            'role_ids' => [],
            'include_nested_groups' => false,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     * @return array{total:int, synced:int, skipped:int}
     */
    private function syncWith(array $entries): array
    {
        $this->mock(LdapService::class, function ($mock) use ($entries): void {
            $mock->shouldReceive('fetchUsers')->andReturn(collect($entries));
        });

        return app(ExternalUserSyncService::class)->syncSource($this->source);
    }

    /**
     * @return array<string, mixed>
     */
    private function entry(string $identifier, ?string $email, array $groups = []): array
    {
        return [
            'identifier' => $identifier,
            'email' => $email,
            'email_verified' => true,
            'first_name' => 'Lia',
            'last_name' => 'Licht',
            'groups' => $groups,
            'meta_data' => ['distinguished_name' => 'uid=' . $identifier . ',dc=example,dc=test'],
        ];
    }

    #[Test]
    public function new_directory_users_are_provisioned_with_defaults_and_group_permissions(): void
    {
        $result = $this->syncWith([$this->entry('lia', 'lia@example.test', [self::GROUP_DN])]);

        $this->assertSame(['total' => 1, 'synced' => 1, 'skipped' => 0], $result);
        $user = User::query()->where('email', 'lia@example.test')->sole();
        $this->assertTrue($user->ad_managed);
        $this->assertTrue($user->hasPermissionTo($this->permission));
        $this->assertTrue($user->calendar_settings()->exists());
        $this->assertSame(1, $this->welcomeMails);
    }

    #[Test]
    public function leaving_the_group_revokes_the_mapped_permission_and_no_second_welcome_mail_is_sent(): void
    {
        $this->syncWith([$this->entry('lia', 'lia@example.test', [self::GROUP_DN])]);
        $this->syncWith([$this->entry('lia', 'lia@example.test', [])]);

        $user = User::query()->where('email', 'lia@example.test')->sole();
        $this->assertFalse($user->fresh()->hasPermissionTo($this->permission));
        $this->assertSame(1, $this->welcomeMails);
    }

    #[Test]
    public function entries_without_identifier_or_email_are_skipped_without_stopping_the_run(): void
    {
        $result = $this->syncWith([
            $this->entry('', 'ohne-id@example.test'),
            $this->entry('ohne-mail', null),
            $this->entry('bea', 'bea@example.test'),
        ]);

        $this->assertSame(['total' => 3, 'synced' => 1, 'skipped' => 2], $result);
        $this->assertTrue(User::query()->where('email', 'bea@example.test')->exists());
        $this->assertFalse(User::query()->where('email', 'ohne-id@example.test')->exists());
    }

    #[Test]
    public function an_existing_local_account_is_linked_by_email_and_follows_the_directory(): void
    {
        // Bewusstes Design (IdentityResolutionService::mayLinkByEmail): das Verzeichnis gilt als
        // vertrauenswürdig, LDAP verknüpft bestehende lokale Konten über die E-Mail.
        $local = User::factory()->create(['email' => 'lokal@example.test', 'ad_managed' => false]);

        $result = $this->syncWith([$this->entry('lokal', 'lokal@example.test', [self::GROUP_DN])]);

        $this->assertSame(1, $result['synced']);
        $this->assertSame(1, User::query()->where('email', 'lokal@example.test')->count());
        $this->assertTrue((bool) $local->fresh()->ad_managed);
        $this->assertSame('ldap', $local->fresh()->auth_provider);
        $this->assertTrue($local->fresh()->hasPermissionTo($this->permission));
    }

    #[Test]
    public function deleting_or_changing_a_mapping_revokes_what_the_sync_granted_before(): void
    {
        $other = Permission::findOrCreate(PermissionEnum::SETTINGS_UPDATE->value, 'web');
        $mapping = ExternalUserGroupMapping::query()->where('source_id', $this->source->id)->sole();
        $mapping->update(['permission_ids' => [$this->permission->id, $other->id]]);
        $this->syncWith([$this->entry('lia', 'lia@example.test', [self::GROUP_DN])]);
        $user = User::query()->where('email', 'lia@example.test')->sole();
        $this->assertTrue($user->fresh()->hasPermissionTo($other));

        // Recht aus dem Mapping entfernt → beim nächsten Lauf weg (vorher: blieb für immer)
        $mapping->update(['permission_ids' => [$this->permission->id]]);
        $this->syncWith([$this->entry('lia', 'lia@example.test', [self::GROUP_DN])]);
        $this->assertFalse($user->fresh()->hasPermissionTo($other));
        $this->assertTrue($user->fresh()->hasPermissionTo($this->permission));

        // Mapping gelöscht → auch das übrige Recht wird entzogen
        $mapping->delete();
        $this->syncWith([$this->entry('lia', 'lia@example.test', [self::GROUP_DN])]);
        $this->assertFalse($user->fresh()->hasPermissionTo($this->permission));
    }
}
