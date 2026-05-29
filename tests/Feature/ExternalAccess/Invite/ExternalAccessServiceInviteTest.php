<?php

namespace Tests\Feature\ExternalAccess\Invite;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\ExternalAccess\DTOs\InviteExternalCommand;
use Artwork\Modules\ExternalAccess\DTOs\TabScopeInput;
use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Artwork\Modules\ExternalAccess\Enums\InviteSource;
use Artwork\Modules\ExternalAccess\Exceptions\ConfidentialMandatoryFieldsMissingException;
use Artwork\Modules\ExternalAccess\Exceptions\InternalUserEmailConflictException;
use Artwork\Modules\ExternalAccess\Exceptions\UnsupportedContactTypeException;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalInvitation;
use Artwork\Modules\ExternalAccess\Models\ExternalLoginToken;
use Artwork\Modules\ExternalAccess\Notifications\ExternalInvitationNotification;
use Artwork\Modules\ExternalAccess\Services\ExternalAccessService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExternalAccessServiceInviteTest extends TestCase
{
    private function service(): ExternalAccessService
    {
        return app(ExternalAccessService::class);
    }

    private function contactType(string $slug = 'freelancer'): CrmContactType
    {
        return CrmContactType::query()->create([
            'name' => ucfirst($slug),
            'slug' => $slug,
        ]);
    }

    private function command(array $overrides = []): InviteExternalCommand
    {
        // Only create a default freelancer type when the test did not supply one,
        // otherwise the unique slug constraint would be violated.
        $contactTypeId = $overrides['crmContactTypeId'] ?? $this->contactType()->id;

        $defaults = [
            'email' => 'external-' . uniqid() . '@example.test',
            'crmContactTypeId' => $contactTypeId,
            'source' => InviteSource::CRM_INDEX,
            'sourceReferenceProjectId' => null,
            'invitedBy' => User::factory()->create(),
            'crmAccessExpiresAt' => null,
            'tabScopes' => [],
            'confidentialFieldValues' => [],
            'publicFieldValues' => ['first_name' => 'Ada', 'last_name' => 'Lovelace'],
        ];

        $merged = array_merge($defaults, $overrides);

        return new InviteExternalCommand(
            email: $merged['email'],
            crmContactTypeId: $merged['crmContactTypeId'],
            source: $merged['source'],
            sourceReferenceProjectId: $merged['sourceReferenceProjectId'],
            invitedBy: $merged['invitedBy'],
            crmAccessExpiresAt: $merged['crmAccessExpiresAt'],
            tabScopes: $merged['tabScopes'],
            confidentialFieldValues: $merged['confidentialFieldValues'],
            publicFieldValues: $merged['publicFieldValues'],
        );
    }

    #[Test]
    public function it_throws_on_internal_user_email_conflict(): void
    {
        $user = User::factory()->create(['email' => 'collision@example.test']);

        $this->expectException(InternalUserEmailConflictException::class);

        $this->service()->invite($this->command(['email' => 'Collision@example.test']));
    }

    #[Test]
    public function it_throws_when_confidential_mandatory_field_missing(): void
    {
        $type = $this->contactType();
        $group = CrmPropertyGroup::query()->create(['name' => 'Vertraulich', 'is_confidential' => true]);
        $property = CrmProperty::query()->create([
            'crm_property_group_id' => $group->id,
            'name' => 'Sozialversicherungsnummer',
            'type' => CrmPropertyTypeEnum::TEXT->value,
        ]);
        $property->contactTypes()->attach($type->id, ['is_required' => true]);

        $this->expectException(ConfidentialMandatoryFieldsMissingException::class);

        $this->service()->invite($this->command(['crmContactTypeId' => $type->id]));
    }

    #[Test]
    public function it_creates_full_set_for_unknown_freelancer_email(): void
    {
        Notification::fake();

        $type = $this->contactType('freelancer');
        $external = $this->service()->invite($this->command([
            'email' => 'Fresh@Example.test',
            'crmContactTypeId' => $type->id,
        ]));

        $this->assertSame('fresh@example.test', $external->email);
        $this->assertNotNull($external->crm_access_expires_at);
        $this->assertTrue($external->isCrmAccessActive());

        $this->assertDatabaseHas('freelancers', ['email' => 'fresh@example.test']);
        $this->assertDatabaseHas('crm_contacts', ['id' => $external->crm_contact_id, 'display_name' => 'Ada Lovelace']);
        $this->assertSame(1, ExternalInvitation::query()->where('external_access_id', $external->id)->count());
        $this->assertSame(1, ExternalLoginToken::query()->where('external_access_id', $external->id)->count());

        Notification::assertSentOnDemand(ExternalInvitationNotification::class);
    }

    #[Test]
    public function it_reuses_existing_external_access_and_adds_second_invitation(): void
    {
        Notification::fake();
        $type = $this->contactType('freelancer');

        $first = $this->service()->invite($this->command([
            'email' => 'reuse@example.test',
            'crmContactTypeId' => $type->id,
        ]));
        $second = $this->service()->invite($this->command([
            'email' => 'reuse@example.test',
            'crmContactTypeId' => $type->id,
        ]));

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, ExternalAccess::query()->where('email', 'reuse@example.test')->count());
        $this->assertSame(2, ExternalInvitation::query()->where('external_access_id', $first->id)->count());
        $this->assertSame(1, Freelancer::query()->where('email', 'reuse@example.test')->count());
    }

    #[Test]
    public function it_adds_additive_scopes_on_reuse(): void
    {
        Notification::fake();
        $type = $this->contactType('freelancer');
        $project = Project::factory()->create();
        $tabA = ProjectTab::factory()->create();
        $tabB = ProjectTab::factory()->create();

        $external = $this->service()->invite($this->command([
            'email' => 'scoped@example.test',
            'crmContactTypeId' => $type->id,
            'source' => InviteSource::PROJECT_TAB,
            'sourceReferenceProjectId' => $project->id,
            'tabScopes' => [new TabScopeInput(
                $tabA->id,
                ExternalAccessType::READ,
                CarbonImmutable::now(),
                CarbonImmutable::now()->addMonth(),
            )],
        ]));

        $this->assertSame(1, $external->scopes()->count());

        $this->service()->invite($this->command([
            'email' => 'scoped@example.test',
            'crmContactTypeId' => $type->id,
            'source' => InviteSource::PROJECT_TAB,
            'sourceReferenceProjectId' => $project->id,
            'tabScopes' => [new TabScopeInput(
                $tabB->id,
                ExternalAccessType::WRITE,
                CarbonImmutable::now(),
                CarbonImmutable::now()->addMonth(),
            )],
        ]));

        $this->assertSame(2, $external->scopes()->count());
    }

    #[Test]
    public function re_granting_same_tab_updates_scope_instead_of_duplicating(): void
    {
        Notification::fake();
        $type = $this->contactType('freelancer');
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();

        $external = $this->service()->invite($this->command([
            'email' => 'sametab@example.test',
            'crmContactTypeId' => $type->id,
            'source' => InviteSource::PROJECT_TAB,
            'sourceReferenceProjectId' => $project->id,
            'tabScopes' => [new TabScopeInput(
                $tab->id,
                ExternalAccessType::READ,
                CarbonImmutable::now(),
                CarbonImmutable::now()->addMonth(),
            )],
        ]));

        $this->service()->invite($this->command([
            'email' => 'sametab@example.test',
            'crmContactTypeId' => $type->id,
            'source' => InviteSource::PROJECT_TAB,
            'sourceReferenceProjectId' => $project->id,
            'tabScopes' => [new TabScopeInput(
                $tab->id,
                ExternalAccessType::WRITE,
                CarbonImmutable::now(),
                CarbonImmutable::now()->addMonth(),
            )],
        ]));

        $this->assertSame(1, $external->scopes()->count());
        $this->assertSame(ExternalAccessType::WRITE, $external->scopes()->first()->access_type);
    }

    #[Test]
    public function it_reactivates_a_revoked_external_access(): void
    {
        Notification::fake();
        $type = $this->contactType('freelancer');

        $external = ExternalAccess::factory()->create([
            'email' => 'revoked@example.test',
            'revoked_at' => now()->subDay(),
        ]);

        $result = $this->service()->invite($this->command([
            'email' => 'revoked@example.test',
            'crmContactTypeId' => $type->id,
        ]));

        $this->assertSame($external->id, $result->id);
        $this->assertNull($result->fresh()->revoked_at);
    }

    #[Test]
    public function it_extends_crm_expiry_but_never_shortens_it(): void
    {
        Notification::fake();
        $type = $this->contactType('freelancer');

        $external = ExternalAccess::factory()->create([
            'email' => 'expiry@example.test',
            'crm_access_expires_at' => CarbonImmutable::now()->addMonth(),
            'revoked_at' => null,
        ]);

        // Later date -> extends (datetime column is second-precision, so compare on seconds).
        $later = CarbonImmutable::now()->addYears(2)->startOfSecond();
        $this->service()->invite($this->command([
            'email' => 'expiry@example.test',
            'crmContactTypeId' => $type->id,
            'crmAccessExpiresAt' => $later,
        ]));
        $this->assertSame(
            $later->format('Y-m-d H:i:s'),
            $external->fresh()->crm_access_expires_at->format('Y-m-d H:i:s'),
        );

        // Earlier date -> does not shorten.
        $earlier = CarbonImmutable::now()->addDays(2);
        $this->service()->invite($this->command([
            'email' => 'expiry@example.test',
            'crmContactTypeId' => $type->id,
            'crmAccessExpiresAt' => $earlier,
        ]));
        $this->assertSame(
            $later->format('Y-m-d H:i:s'),
            $external->fresh()->crm_access_expires_at->format('Y-m-d H:i:s'),
        );
    }

    #[Test]
    public function it_reuses_existing_crm_contact_when_email_matches_a_freelancer(): void
    {
        Notification::fake();
        $type = $this->contactType('freelancer');

        $freelancer = Freelancer::factory()->create(['email' => 'known@example.test']);
        $freelancer->createCrmContact();
        $existingContactId = $freelancer->crmContact()->first()->id;

        $external = $this->service()->invite($this->command([
            'email' => 'known@example.test',
            'crmContactTypeId' => $type->id,
        ]));

        $this->assertSame($existingContactId, $external->crm_contact_id);
        $this->assertSame(1, Freelancer::query()->where('email', 'known@example.test')->count());
    }

    #[Test]
    public function it_throws_for_a_non_invitable_contact_type(): void
    {
        $type = $this->contactType('some_custom_type');

        $this->expectException(UnsupportedContactTypeException::class);

        $this->service()->invite($this->command(['crmContactTypeId' => $type->id]));
    }

    #[Test]
    public function it_invites_an_artist_even_though_it_has_no_email_column(): void
    {
        Notification::fake();
        $type = $this->contactType('artist');

        $external = $this->service()->invite($this->command([
            'email' => 'artist@example.test',
            'crmContactTypeId' => $type->id,
            'publicFieldValues' => ['name' => 'The Artist'],
        ]));

        $this->assertSame('artist@example.test', $external->email);
        $this->assertDatabaseHas('artists', ['name' => 'The Artist']);
        $this->assertDatabaseHas('crm_contacts', ['id' => $external->crm_contact_id]);
    }

    #[Test]
    public function it_writes_confidential_field_values_to_crm_property_values(): void
    {
        Notification::fake();
        $type = $this->contactType('freelancer');
        $group = CrmPropertyGroup::query()->create(['name' => 'Vertraulich', 'is_confidential' => true]);
        $property = CrmProperty::query()->create([
            'crm_property_group_id' => $group->id,
            'name' => 'IBAN',
            'type' => CrmPropertyTypeEnum::TEXT->value,
        ]);
        $property->contactTypes()->attach($type->id, ['is_required' => true]);

        $external = $this->service()->invite($this->command([
            'email' => 'confidential@example.test',
            'crmContactTypeId' => $type->id,
            'confidentialFieldValues' => [(string) $property->id => 'DE123'],
        ]));

        $this->assertDatabaseHas('crm_property_values', [
            'crm_contact_id' => $external->crm_contact_id,
            'crm_property_id' => $property->id,
            'value' => 'DE123',
        ]);
    }
}
