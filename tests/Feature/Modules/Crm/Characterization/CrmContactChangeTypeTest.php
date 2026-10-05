<?php

namespace Tests\Feature\Modules\Crm\Characterization;

use Artwork\Modules\Crm\Enums\CrmSystemContactTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Hält das heutige Verhalten des Kontakttyp-Wechsels fest: nur `crm manager`, nur
 * freie (nicht entity-gebundene, nicht gespiegelte) Kontakte, kein Wechsel in einen
 * gespiegelten Systemtyp; Werte nicht übernommener Eigenschaften werden gelöscht.
 */
final class CrmContactChangeTypeTest extends FeatureTestCase
{
    #[Test]
    public function change_type_moves_contact_and_drops_values_of_unassigned_properties(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $shared = CrmProperty::factory()->create();
        $onlyOld = CrmProperty::factory()->create();
        $oldType = CrmContactType::factory()->create();
        $newType = CrmContactType::factory()->create();
        $oldType->properties()->attach([$shared->id => ['sort_order' => 0], $onlyOld->id => ['sort_order' => 1]]);
        $newType->properties()->attach($shared->id, ['sort_order' => 0]);
        $contact = CrmContact::factory()->create(['crm_contact_type_id' => $oldType->id]);
        foreach ([$shared, $onlyOld] as $property) {
            CrmPropertyValue::query()->create([
                'crm_contact_id' => $contact->id,
                'crm_property_id' => $property->id,
                'value' => 'wert',
            ]);
        }

        $this->patch(route('crm.contacts.change-type', $contact), ['crm_contact_type_id' => $newType->id])
            ->assertRedirect();

        $this->assertSame($newType->id, $contact->fresh()->crm_contact_type_id);
        $this->assertDatabaseHas('crm_property_values', [
            'crm_contact_id' => $contact->id,
            'crm_property_id' => $shared->id,
        ]);
        $this->assertDatabaseMissing('crm_property_values', [
            'crm_contact_id' => $contact->id,
            'crm_property_id' => $onlyOld->id,
        ]);
    }

    #[Test]
    public function change_type_validates_target_type(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $contact = CrmContact::factory()->create();

        $this->patch(route('crm.contacts.change-type', $contact), ['crm_contact_type_id' => 999999])
            ->assertSessionHasErrors('crm_contact_type_id');
    }

    #[Test]
    public function change_type_into_a_mirrored_system_type_is_rejected_with_422(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $contact = CrmContact::factory()->create();
        $originalTypeId = $contact->crm_contact_type_id;
        $mirrored = CrmContactType::factory()->create([
            'slug' => CrmSystemContactTypeEnum::FREELANCER->value,
            'is_system' => true,
        ]);

        $this->patch(route('crm.contacts.change-type', $contact), ['crm_contact_type_id' => $mirrored->id])
            ->assertStatus(422);

        $this->assertSame($originalTypeId, $contact->fresh()->crm_contact_type_id);
    }

    #[Test]
    public function change_type_of_mirrored_contact_is_forbidden(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $mirrored = CrmContactType::factory()->create([
            'slug' => CrmSystemContactTypeEnum::USER->value,
            'is_system' => true,
        ]);
        $contact = CrmContact::factory()->create(['crm_contact_type_id' => $mirrored->id]);
        $target = CrmContactType::factory()->create();

        $this->patch(route('crm.contacts.change-type', $contact), ['crm_contact_type_id' => $target->id])
            ->assertForbidden();

        $this->assertSame($mirrored->id, $contact->fresh()->crm_contact_type_id);
    }

    #[Test]
    public function change_type_of_entity_bound_contact_is_forbidden(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_MANAGER->value);
        $contact = CrmContact::factory()->create([
            'entity_type' => 'artist',
            'entity_id' => 1,
        ]);
        $originalTypeId = $contact->crm_contact_type_id;
        $target = CrmContactType::factory()->create();

        $this->patch(route('crm.contacts.change-type', $contact), ['crm_contact_type_id' => $target->id])
            ->assertForbidden();

        $this->assertSame($originalTypeId, $contact->fresh()->crm_contact_type_id);
    }

    #[Test]
    public function change_type_is_forbidden_for_crm_viewer(): void
    {
        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $contact = CrmContact::factory()->create();
        $originalTypeId = $contact->crm_contact_type_id;
        $target = CrmContactType::factory()->create();

        $this->patch(route('crm.contacts.change-type', $contact), ['crm_contact_type_id' => $target->id])
            ->assertForbidden();

        $this->assertSame($originalTypeId, $contact->fresh()->crm_contact_type_id);
    }
}
