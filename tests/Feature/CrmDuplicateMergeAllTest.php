<?php

namespace Tests\Feature;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Crm\Services\CrmContactService;
use PHPUnit\Framework\Attributes\Test;

final class CrmDuplicateMergeAllTest extends FeatureTestCase
{
    private function makeType(): CrmContactType
    {
        return CrmContactType::create([
            'name' => 'Merge Test Typ',
            'slug' => 'merge-test-typ',
            'is_system' => false,
            'is_active' => true,
        ]);
    }

    private function makeContact(CrmContactType $type, string $name, ?string $email = null): CrmContact
    {
        $contact = CrmContact::create([
            'crm_contact_type_id' => $type->id,
            'display_name' => $name,
            'is_active' => true,
        ]);

        if ($email !== null) {
            $property = CrmProperty::firstOrCreate(
                ['name' => 'Email'],
                [
                    'crm_property_group_id' => CrmPropertyGroup::firstOrCreate(['name' => 'Merge Test Gruppe'])->id,
                    'type' => 'text',
                    'sort_order' => 0,
                ]
            );
            app(CrmContactService::class)->savePropertyValue($contact, $property->id, $email);
        }

        return $contact;
    }

    #[Test]
    public function merge_all_merges_every_cluster_with_default_primary(): void
    {
        $this->actingAsAdmin();
        $type = $this->makeType();

        // Cluster 1: drei gleiche Namen — der älteste (a1) bleibt
        $a1 = $this->makeContact($type, 'Anna Beispiel');
        $a2 = $this->makeContact($type, 'Anna Beispiel');
        $a3 = $this->makeContact($type, 'anna beispiel');

        // Cluster 2: gleiche E-Mail bei unterschiedlichen Namen — b1 bleibt
        $b1 = $this->makeContact($type, 'Bert Muster', 'bert@example.org');
        $b2 = $this->makeContact($type, 'Berthold Muster', 'bert@example.org');

        // Kein Duplikat
        $solo = $this->makeContact($type, 'Carla Solo');

        $this->post(route('crm.duplicates.merge-all'))
            ->assertRedirect();

        $this->assertNull(CrmContact::find($a2->id), 'a2 should be soft-deleted');
        $this->assertNull(CrmContact::find($a3->id), 'a3 should be soft-deleted');
        $this->assertNull(CrmContact::find($b2->id), 'b2 should be soft-deleted');
        $this->assertNotNull(CrmContact::find($a1->id));
        $this->assertNotNull(CrmContact::find($b1->id));
        $this->assertNotNull(CrmContact::find($solo->id));

        $this->assertSoftDeleted('crm_contacts', ['id' => $a2->id]);
        $this->assertSame(3, CrmContact::where('crm_contact_type_id', $type->id)->count());
    }

    #[Test]
    public function merge_all_keeps_entity_bound_contacts_and_prefers_them_as_primary(): void
    {
        $this->actingAsAdmin();
        $type = $this->makeType();

        $plain = $this->makeContact($type, 'Doris Profil');
        $linked = $this->makeContact($type, 'Doris Profil');
        $linked->update(['entity_type' => 'user', 'entity_id' => 1]);

        $this->post(route('crm.duplicates.merge-all'))
            ->assertRedirect();

        // Der profilverknüpfte Kontakt bleibt Hauptkontakt, der einfache wird eingeschmolzen
        $this->assertNotNull(CrmContact::find($linked->id));
        $this->assertNull(CrmContact::find($plain->id));
    }

    #[Test]
    public function merge_all_resolves_chained_clusters_across_passes(): void
    {
        $this->actingAsAdmin();
        $type = $this->makeType();

        // A und B teilen den Namen, B und C die E-Mail: erst geht B in A auf (E-Mail
        // wandert zu A), im nächsten Pass matcht C über die E-Mail auf A.
        $a = $this->makeContact($type, 'Emil Kette');
        $b = $this->makeContact($type, 'Emil Kette', 'emil@example.org');
        $c = $this->makeContact($type, 'E. Kette', 'emil@example.org');

        $this->post(route('crm.duplicates.merge-all'))
            ->assertRedirect();

        $this->assertNotNull(CrmContact::find($a->id));
        $this->assertNull(CrmContact::find($b->id));
        $this->assertNull(CrmContact::find($c->id));
    }

    #[Test]
    public function merge_all_requires_crm_manager_permission(): void
    {
        $this->actingAsUserWith([]);

        $this->post(route('crm.duplicates.merge-all'))
            ->assertForbidden();
    }
}
