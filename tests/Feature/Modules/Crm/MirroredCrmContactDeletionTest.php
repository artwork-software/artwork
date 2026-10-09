<?php

namespace Tests\Feature\Modules\Crm;

use Artwork\Core\Console\Commands\UpdateArtwork;
use Artwork\Modules\ArtistResidency\Models\Artist;
use Artwork\Modules\Crm\Enums\CrmSystemContactTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Migrations\Migration;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\Feature\FeatureTestCase;

/**
 * Gespiegelte CRM-Kontakte (Nutzer*innen, Freelancer, Dienstleister) sind im CRM nicht löschbar
 * und werden deshalb mit ihrer Quell-Entität endgültig gelöscht — samt Eigenschaftswerten.
 */
final class MirroredCrmContactDeletionTest extends FeatureTestCase
{
    private CrmProperty $property;

    protected function setUp(): void
    {
        parent::setUp();

        $systemTypes = [
            CrmSystemContactTypeEnum::USER,
            CrmSystemContactTypeEnum::FREELANCER,
            CrmSystemContactTypeEnum::SERVICE_PROVIDER,
            CrmSystemContactTypeEnum::ARTIST,
        ];

        foreach ($systemTypes as $systemType) {
            if (!CrmContactType::query()->where('slug', $systemType->value)->exists()) {
                CrmContactType::factory()->create(['slug' => $systemType->value, 'is_system' => true]);
            }
        }

        $this->property = CrmProperty::factory()->create();
    }

    #[Test]
    public function deleting_a_user_removes_the_mirrored_crm_contact_and_its_values(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();
        $contact = $this->mirroredContactFor($user);
        $otherUser = User::factory()->create();
        $otherContact = $this->mirroredContactFor($otherUser);

        $this->delete(route('user.destroy', $user))->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('crm_contacts', ['id' => $contact->id]);
        $this->assertDatabaseMissing('crm_property_values', ['crm_contact_id' => $contact->id]);
        $this->assertDatabaseHas('crm_contacts', ['id' => $otherContact->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('crm_property_values', ['crm_contact_id' => $otherContact->id]);
    }

    #[Test]
    public function deleting_a_freelancer_removes_the_mirrored_crm_contact_and_its_values(): void
    {
        $this->actingAsAdmin();
        $freelancer = Freelancer::factory()->create();
        $contact = $this->mirroredContactFor($freelancer);

        $this->delete(route('freelancer.destroy', $freelancer))->assertRedirect();

        $this->assertDatabaseMissing('freelancers', ['id' => $freelancer->id]);
        $this->assertDatabaseMissing('crm_contacts', ['id' => $contact->id]);
        $this->assertDatabaseMissing('crm_property_values', ['crm_contact_id' => $contact->id]);
    }

    #[Test]
    public function deleting_a_service_provider_removes_the_mirrored_crm_contact_and_its_values(): void
    {
        $serviceProvider = ServiceProvider::factory()->create();
        $contact = $this->mirroredContactFor($serviceProvider);

        $serviceProvider->delete();

        $this->assertDatabaseMissing('crm_contacts', ['id' => $contact->id]);
        $this->assertDatabaseMissing('crm_property_values', ['crm_contact_id' => $contact->id]);
    }

    #[Test]
    public function deleting_an_entity_also_removes_a_mirrored_contact_already_in_the_trash(): void
    {
        $freelancer = Freelancer::factory()->create();
        $contact = $this->mirroredContactFor($freelancer);
        $contact->delete();

        $freelancer->delete();

        $this->assertDatabaseMissing('crm_contacts', ['id' => $contact->id]);
    }

    #[Test]
    public function migration_removes_orphaned_mirrored_contacts_and_the_placeholder_contact_only(): void
    {
        $orphanedUserContact = $this->contactOfType(CrmSystemContactTypeEnum::USER, User::class, 999_999_901);
        $orphanedFreelancerContact = $this->contactOfType(
            CrmSystemContactTypeEnum::FREELANCER,
            Freelancer::class,
            999_999_902
        );
        $orphanedServiceProviderContact = $this->contactOfType(
            CrmSystemContactTypeEnum::SERVICE_PROVIDER,
            ServiceProvider::class,
            999_999_903
        );
        $placeholder = User::factory()->create(['email' => self::placeholderEmail()]);
        $placeholderContact = $this->mirroredContactFor($placeholder);
        $liveUserContact = $this->mirroredContactFor(User::factory()->create());
        // Künstler*innen sind nicht gespiegelt — ihre Kontakte fasst die Bereinigung nicht an
        $artistContact = $this->contactOfType(CrmSystemContactTypeEnum::ARTIST, Artist::class, 999_999_904);
        $freeContact = CrmContact::factory()->create();

        $this->runCleanupMigration();

        $removedContacts = [
            $orphanedUserContact,
            $orphanedFreelancerContact,
            $orphanedServiceProviderContact,
            $placeholderContact,
        ];
        foreach ($removedContacts as $removed) {
            $this->assertDatabaseMissing('crm_contacts', ['id' => $removed->id]);
            $this->assertDatabaseMissing('crm_property_values', ['crm_contact_id' => $removed->id]);
        }
        foreach ([$liveUserContact, $artistContact, $freeContact] as $kept) {
            $this->assertDatabaseHas('crm_contacts', ['id' => $kept->id]);
        }
        $this->assertNull($placeholder->fresh()->crm_contact_id);
    }

    #[Test]
    public function crm_sync_of_artwork_update_does_not_create_a_contact_for_the_deleted_user_placeholder(): void
    {
        $placeholder = User::factory()->create(['email' => self::placeholderEmail()]);
        $realUser = User::factory()->create();

        $command = app(UpdateArtwork::class);
        $command->setOutput(new OutputStyle(new ArrayInput([]), new NullOutput()));
        (new \ReflectionMethod($command, 'syncCrmContacts'))->invoke($command);

        $this->assertNull($placeholder->fresh()->crm_contact_id);
        $this->assertDatabaseMissing('crm_contacts', ['entity_type' => User::class, 'entity_id' => $placeholder->id]);
        $this->assertNotNull($realUser->fresh()->crm_contact_id);
    }

    private function mirroredContactFor(User|Freelancer|ServiceProvider $entity): CrmContact
    {
        $entity->createCrmContact();
        $contact = CrmContact::query()->findOrFail($entity->fresh()->crm_contact_id);
        $this->addPropertyValue($contact);

        return $contact;
    }

    private function contactOfType(CrmSystemContactTypeEnum $type, string $entityType, int $entityId): CrmContact
    {
        $contact = CrmContact::factory()->create([
            'crm_contact_type_id' => CrmContactType::query()->where('slug', $type->value)->value('id'),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);
        $this->addPropertyValue($contact);

        return $contact;
    }

    private function addPropertyValue(CrmContact $contact): void
    {
        CrmPropertyValue::query()->create([
            'crm_contact_id' => $contact->id,
            'crm_property_id' => $this->property->id,
            'value' => 'personenbezogener Wert',
        ]);
    }

    private static function placeholderEmail(): string
    {
        return config('artwork.deleted_user_email', 'deleted-user@artwork.local');
    }

    private function runCleanupMigration(): void
    {
        /** @var Migration $migration */
        $migration = require database_path('migrations/2026_10_06_170146_delete_orphaned_mirrored_crm_contacts.php');
        $migration->up();
    }
}
