<?php

namespace Tests\Feature\Modules\Crm;

use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * artwork:migrate-to-crm spiegelt Nutzer*innen ins CRM. Der Platzhalter „Deleted user“ ist keine
 * echte Person und darf keinen Kontakt bekommen — sonst legt der Befehl auf Instanzen, auf denen er
 * erst nach der Bereinigungs-Migration läuft, den entfernten Kontakt wieder an.
 */
final class MigrateToCrmCommandTest extends FeatureTestCase
{
    #[Test]
    public function migration_does_not_create_a_contact_for_the_deleted_user_placeholder(): void
    {
        $placeholder = User::factory()->create([
            'email' => config('artwork.deleted_user_email', 'deleted-user@artwork.local'),
        ]);
        $realUser = User::factory()->create();

        $this->artisan('artwork:migrate-to-crm')->assertSuccessful();

        $this->assertNull($placeholder->fresh()->crm_contact_id);
        $this->assertDatabaseMissing('crm_contacts', ['entity_type' => User::class, 'entity_id' => $placeholder->id]);
        $this->assertNotNull($realUser->fresh()->crm_contact_id);
        $this->assertDatabaseHas('crm_contacts', ['entity_type' => User::class, 'entity_id' => $realUser->id]);
    }
}
