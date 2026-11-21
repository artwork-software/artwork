<?php

namespace Tests\Feature;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\ExternalAccess\Enums\InviteSource;
use Artwork\Modules\ExternalAccess\Settings\ExternalAccessSettings;
use Artwork\Modules\ExternalUserManagement\Service\CredentialLoginService;
use Artwork\Modules\ExternalUserManagement\Service\LoginAccountKey;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

/**
 * Audit 18.11.2026: Login-Drossel über Akzentvarianten (M5), Einladung an fremden CRM-Kontakt (M3,
 * Sichtrecht), signierter Download deaktivierter Konten (N10), Externen-Liste versteckter Tabs (N18),
 * ausgelieferte Dateien (N22, N23).
 */
final class SecurityAuditAccessHardening20261118RegressionTest extends FeatureTestCase
{
    private const PASSWORD = 'Str0ng!Passw0rd#2026';

    private function localUser(string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'password' => Hash::make(self::PASSWORD),
            'auth_provider' => 'local',
        ]);
    }

    private function enableExternalAccess(): void
    {
        $settings = app(ExternalAccessSettings::class);
        $settings->enabled = true;
        $settings->save();
    }

    // ---------------------------------------------------------------------------------------
    // M5: Login-Drossel pro Konto
    // ---------------------------------------------------------------------------------------

    #[Test]
    public function accent_variants_of_an_address_share_the_account_bucket_of_the_web_login(): void
    {
        $local = 'anna-' . Str::lower(Str::random(6));
        $user = $this->localUser($local . '@haus.example');

        $keyFor = static fn (string $email): string => RateLimiter::limiter('login')(
            Request::create('/login', 'POST', ['email' => $email])
        )[1]->key;

        $this->assertSame('account|id:' . $user->id, $keyFor($local . '@haus.example'));
        $this->assertSame('account|id:' . $user->id, $keyFor('á' . substr($local, 1) . '@haus.example'));
        $this->assertSame('account|id:' . $user->id, $keyFor('  ' . Str::upper($local) . '@Haus.Example '));

        // Unbekannte Adressen zählen wie bisher über die normalisierte Eingabe
        $this->assertSame('account|mail:niemand@haus.example', $keyFor(' Niemand@Haus.example'));
        $this->assertSame('account|', $keyFor(''));
    }

    #[Test]
    public function accent_variants_of_an_address_share_the_account_bucket_of_the_app_login(): void
    {
        $local = 'anna-' . Str::lower(Str::random(6));
        $user = $this->localUser($local . '@haus.example');

        $keyFor = static fn (string $email): string => RateLimiter::limiter('app-login')(
            Request::create('/api/app/v1/auth/login', 'POST', ['email' => $email])
        )[2]->key;

        $expected = 'app-login:account:' . hash('sha256', 'id:' . $user->id);
        $this->assertSame($expected, $keyFor($local . '@haus.example'));
        $this->assertSame($expected, $keyFor('à' . substr($local, 1) . '@HAUS.example'));
        $this->assertSame(
            'app-login:account:' . hash('sha256', 'mail:niemand@haus.example'),
            $keyFor('Niemand@haus.example')
        );
    }

    #[Test]
    public function the_web_login_blocks_after_ten_attempts_spread_over_accent_variants(): void
    {
        $suffix = Str::lower(Str::random(6));
        $this->localUser('anna-' . $suffix . '@haus.example');
        $variants = ['á', 'à', 'â', 'ä', 'ã', 'å', 'ā', 'ă', 'ą', 'Á'];

        foreach ($variants as $variant) {
            $this->post(route('login'), [
                'email' => $variant . 'nna-' . $suffix . '@haus.example',
                'password' => 'falsch',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('login'), ['email' => 'Ànna-' . $suffix . '@haus.example', 'password' => 'falsch'])
            ->assertStatus(429);
    }

    #[Test]
    public function a_login_with_an_accent_variant_fails_but_letter_case_still_does_not_matter(): void
    {
        $local = 'anna-' . Str::lower(Str::random(6));
        $user = $this->localUser($local . '@haus.example');
        $service = app(CredentialLoginService::class);

        $this->assertNull($service->attempt('á' . substr($local, 1) . '@haus.example', self::PASSWORD));

        $resolved = $service->attempt('  ' . Str::upper($local) . '@Haus.Example ', self::PASSWORD);
        $this->assertNotNull($resolved);
        $this->assertTrue($user->is($resolved));
    }

    #[Test]
    public function an_account_with_an_umlaut_address_still_logs_in_with_its_own_spelling(): void
    {
        $suffix = Str::lower(Str::random(6));
        $user = $this->localUser('jörg-' . $suffix . '@haus.example');
        $service = app(CredentialLoginService::class);

        // Zusammengesetzte Schreibweise (o + Trema, etwa aus der macOS-Zwischenablage) ist dieselbe Adresse
        $decomposed = "jo\u{0308}rg-" . $suffix . '@haus.example';
        $this->assertTrue(LoginAccountKey::sameAddress($user->email, $decomposed));
        $this->assertTrue($user->is($service->attempt('JÖRG-' . $suffix . '@haus.example', self::PASSWORD)));
        $this->assertTrue($user->is($service->attempt($decomposed, self::PASSWORD)));

        $this->assertNull($service->attempt('jorg-' . $suffix . '@haus.example', self::PASSWORD));
    }

    // ---------------------------------------------------------------------------------------
    // N10: signierter App-Download
    // ---------------------------------------------------------------------------------------

    #[Test]
    public function a_signed_app_download_is_refused_for_a_deactivated_account(): void
    {
        Storage::fake();
        $project = Project::factory()->create();
        $member = User::factory()->create();
        $project->users()->attach($member->id);
        $file = ProjectFile::query()->forceCreate([
            'project_id' => $project->id,
            'name' => 'Plan.pdf',
            'basename' => uniqid() . 'Plan.pdf',
        ]);
        Storage::put($file->storagePath(), 'content');
        $url = URL::temporarySignedRoute('app.v1.files.download', now()->addMinutes(5), [
            'projectFile' => $file->id,
            'user' => $member->id,
        ]);

        $this->get($url)->assertOk();

        $member->forceFill(['deactivated_at' => now()])->save();

        $this->get($url)->assertForbidden();
    }

    // ---------------------------------------------------------------------------------------
    // N18: Externe eines Tabs
    // ---------------------------------------------------------------------------------------

    #[Test]
    public function the_externals_of_a_tab_are_only_listed_for_people_who_may_see_the_tab(): void
    {
        $this->enableExternalAccess();
        $project = Project::factory()->create();
        $member = User::factory()->create();
        $project->users()->attach($member->id);
        $visibleTab = ProjectTab::factory()->create(['visible_for_all' => true]);
        $hiddenTab = ProjectTab::factory()->create(['visible_for_all' => false]);
        $this->actingAs($member);

        $this->getJson(route('projects.tabs.externals.index', [$project->id, $visibleTab->id]))
            ->assertOk()
            ->assertJsonPath('externals', []);
        $this->getJson(route('projects.tabs.externals.index', [$project->id, $hiddenTab->id]))
            ->assertForbidden();

        $this->actingAsAdmin();
        $this->getJson(route('projects.tabs.externals.index', [$project->id, $hiddenTab->id]))->assertOk();
    }

    // ---------------------------------------------------------------------------------------
    // M3: Einladung an einen bestehenden CRM-Kontakt
    // ---------------------------------------------------------------------------------------

    #[Test]
    public function inviting_an_existing_crm_contact_requires_the_right_to_view_the_crm(): void
    {
        $this->enableExternalAccess();
        $type = CrmContactType::query()->create(['name' => 'Agentur', 'slug' => 'agency-' . uniqid()]);
        $contact = CrmContact::query()->create([
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Agentur X',
            'is_active' => true,
        ]);
        $payload = [
            'crm_contact_id' => $contact->id,
            'email' => 'privat-' . uniqid() . '@example.test',
            'source' => InviteSource::CRM_CONTACT->value,
        ];

        $this->actingAsUserWith([PermissionEnum::INVITE_EXTERNAL]);
        $this->getJson(route('crm.externals.contacts.invite-info', $contact->id))->assertForbidden();
        $this->postJson(route('crm.externals.invitations.store'), $payload)->assertForbidden();
        $this->assertDatabaseMissing('external_accesses', ['crm_contact_id' => $contact->id]);

        $this->actingAsUserWith([PermissionEnum::INVITE_EXTERNAL, PermissionEnum::CRM_VIEW]);
        $this->getJson(route('crm.externals.contacts.invite-info', $contact->id))
            ->assertOk()
            ->assertJsonPath('contact.id', $contact->id);
        $this->postJson(route('crm.externals.invitations.store'), $payload)->assertCreated();
        $this->assertDatabaseHas('external_accesses', ['crm_contact_id' => $contact->id]);
    }

    // ---------------------------------------------------------------------------------------
    // N22, N23: ausgelieferte Dateien
    // ---------------------------------------------------------------------------------------

    #[Test]
    public function the_image_excludes_root_notes_and_local_working_state(): void
    {
        $lines = array_map('trim', (array) file(base_path('.dockerignore')));

        foreach (['*.md', '!README.md', '!LICENSE.md', 'docs/security', 'public/hot'] as $pattern) {
            $this->assertContains($pattern, $lines, $pattern);
        }
        // Ausnahmen wirken nur nach dem Muster, das sie aufheben
        $this->assertLessThan(array_search('!README.md', $lines, true), array_search('*.md', $lines, true));
        // storage/app enthält ausgelieferte Standarddateien (Flaggen, Platzhalter) und bleibt im Image
        $this->assertNotContains('storage/app', $lines);
    }

    #[Test]
    public function shipped_defaults_log_at_info_level_and_offer_only_current_tls_versions(): void
    {
        $this->assertMatchesRegularExpression(
            '/^LOG_LEVEL=info$/m',
            (string) file_get_contents(base_path('.env.example'))
        );

        $nginx = (string) file_get_contents(base_path('dockerfiles/nginx.conf'));
        $this->assertMatchesRegularExpression('/^\s*ssl_protocols TLSv1\.2 TLSv1\.3;/m', $nginx);
    }

    #[Test]
    public function an_unknown_login_input_never_shares_the_throttle_key_of_an_account(): void
    {
        $user = $this->localUser('schluessel-' . Str::lower(Str::random(6)) . '@haus.example');

        $accountKey = LoginAccountKey::for($user->email);

        $this->assertSame('id:' . $user->id, $accountKey);
        // wer die Kontonummer als "Adresse" eingibt, darf nicht auf das fremde Konto zählen
        $this->assertNotSame($accountKey, LoginAccountKey::for('id:' . $user->id));
        $this->assertSame('mail:niemand@haus.example', LoginAccountKey::for(' Niemand@haus.example '));
    }
}
