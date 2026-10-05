<?php

namespace Tests\Feature\ExternalAccess\Invite;

use Artwork\Modules\ArtistResidency\Models\Artist;
use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\ExternalAccess\DTOs\InviteExternalCommand;
use Artwork\Modules\ExternalAccess\Enums\InviteSource;
use Artwork\Modules\ExternalAccess\Exceptions\EmailLinkedToOtherContactException;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Services\CrmContactEmailResolver;
use Artwork\Modules\ExternalAccess\Services\ExternalAccessService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

final class InviteExistingContactTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function command(array $overrides = []): InviteExternalCommand
    {
        $defaults = [
            'email' => '',
            'crmContactTypeId' => null,
            'source' => InviteSource::CRM_CONTACT,
            'sourceReferenceProjectId' => null,
            'invitedBy' => User::factory()->create(),
            'crmAccessExpiresAt' => null,
            'tabScopes' => [],
            'confidentialFieldValues' => [],
            'publicFieldValues' => [],
            'crmContactId' => null,
        ];

        return new InviteExternalCommand(...array_merge($defaults, $overrides));
    }

    private function freelancerContact(string $email = 'ada@example.test'): CrmContact
    {
        CrmContactType::query()->firstOrCreate(['slug' => 'freelancer'], ['name' => 'Freelancer']);
        $fl = Freelancer::factory()->create(['email' => $email]);
        $fl->createCrmContact();

        return $fl->crmContact()->firstOrFail();
    }

    #[Test]
    public function existing_contact_is_invited_with_its_stored_email(): void
    {
        $contact = $this->freelancerContact('ada@example.test');

        $external = app(ExternalAccessService::class)->invite($this->command(['crmContactId' => $contact->id]));

        $this->assertSame('ada@example.test', $external->email);
        $this->assertSame($contact->id, (int) $external->crm_contact_id);
    }

    #[Test]
    public function override_email_is_stored_on_the_access_only(): void
    {
        $contact = $this->freelancerContact('ada@example.test');

        $external = app(ExternalAccessService::class)->invite($this->command([
            'crmContactId' => $contact->id,
            'email' => 'Booking@Agency.test',
        ]));

        $this->assertSame('booking@agency.test', $external->email);
        $this->assertSame('ada@example.test', $contact->getSourceEntity()->fresh()->email);
    }

    #[Test]
    public function email_is_stored_on_contact_without_email(): void
    {
        $type = CrmContactType::query()->create(['name' => 'Agentur', 'slug' => 'agency']);
        $group = CrmPropertyGroup::query()->create(['name' => 'Kontakt', 'is_confidential' => false]);
        $property = CrmProperty::query()->create([
            'crm_property_group_id' => $group->id,
            'name' => 'Email',
            'type' => CrmPropertyTypeEnum::TEXT->value,
        ]);
        $property->contactTypes()->attach($type->id, ['is_required' => false]);
        $contact = CrmContact::query()->create(['crm_contact_type_id' => $type->id, 'display_name' => 'Agentur X', 'is_active' => true]);

        $external = app(ExternalAccessService::class)->invite($this->command([
            'crmContactId' => $contact->id,
            'email' => 'agentur@example.test',
        ]));

        $this->assertSame('agentur@example.test', $external->email);
        $this->assertSame('agentur@example.test', app(CrmContactEmailResolver::class)->resolve($contact));
    }

    #[Test]
    public function email_already_linked_to_other_contact_is_rejected(): void
    {
        $other = $this->freelancerContact('other@example.test');
        ExternalAccess::factory()->active()->create(['email' => 'taken@example.test', 'crm_contact_id' => $other->id]);
        $contact = $this->freelancerContact('ada@example.test');

        $this->expectException(EmailLinkedToOtherContactException::class);

        app(ExternalAccessService::class)->invite($this->command(['crmContactId' => $contact->id, 'email' => 'taken@example.test']));
    }

    #[Test]
    public function artist_invitation_stores_email_and_is_resolved_on_reinvite(): void
    {
        $type = CrmContactType::query()->create(['name' => 'Künstler*in', 'slug' => 'artist', 'is_system' => true]);

        $service = app(ExternalAccessService::class);
        $first = $service->invite($this->command([
            'source' => InviteSource::CRM_INDEX,
            'crmContactTypeId' => $type->id,
            'email' => 'artist@example.test',
            'publicFieldValues' => ['name' => 'Compagnie Luna'],
        ]));

        $artist = Artist::query()->where('email', 'artist@example.test')->first();
        $this->assertNotNull($artist);
        $this->assertSame($artist->crmContact->id, (int) $first->crm_contact_id);

        // Zugang löschen und erneut per E-Mail einladen → bestehender Künstler-Kontakt wird gefunden
        $first->delete();
        $second = $service->invite($this->command([
            'source' => InviteSource::CRM_INDEX,
            'crmContactTypeId' => $type->id,
            'email' => 'artist@example.test',
            'publicFieldValues' => ['name' => 'Egal'],
        ]));

        $this->assertSame($artist->crmContact->id, (int) $second->crm_contact_id);
        $this->assertSame(1, Artist::query()->where('email', 'artist@example.test')->count());
    }

    #[Test]
    public function invite_info_endpoint_returns_contact_email_and_accesses(): void
    {
        $this->actingAsUserWith([PermissionEnum::INVITE_EXTERNAL]);
        $contact = $this->freelancerContact('ada@example.test');
        ExternalAccess::factory()->active()->create(['email' => 'ada@example.test', 'crm_contact_id' => $contact->id]);

        $this->getJson(route('crm.externals.contacts.invite-info', $contact->id))
            ->assertOk()
            ->assertJsonPath('contact.email', 'ada@example.test')
            ->assertJsonCount(1, 'accesses')
            ->assertJsonStructure(['defaults' => ['crm_access_expires_at', 'tab_valid_from', 'tab_valid_to']]);
    }

    #[Test]
    public function http_invite_of_existing_contact_without_email_requires_email(): void
    {
        $this->actingAsUserWith([PermissionEnum::INVITE_EXTERNAL]);
        $type = CrmContactType::query()->create(['name' => 'Agentur', 'slug' => 'agency']);
        $contact = CrmContact::query()->create(['crm_contact_type_id' => $type->id, 'display_name' => 'Agentur X', 'is_active' => true]);

        $this->postJson(route('crm.externals.invitations.store'), [
            'crm_contact_id' => $contact->id,
            'source' => InviteSource::CRM_CONTACT->value,
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);

        $this->postJson(route('crm.externals.invitations.store'), [
            'crm_contact_id' => $contact->id,
            'email' => 'agentur@example.test',
            'source' => InviteSource::CRM_CONTACT->value,
        ])->assertCreated();
    }
}
