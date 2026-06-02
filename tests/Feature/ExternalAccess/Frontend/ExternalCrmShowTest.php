<?php

namespace Tests\Feature\ExternalAccess\Frontend;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\ExternalAccess\Http\Controllers\ExternalCrmController;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExternalCrmShowTest extends TestCase
{
    /**
     * @return array{
     *     contactType: CrmContactType,
     *     contact: CrmContact,
     *     external: ExternalAccess,
     * }
     */
    private function makeContactWithExternalAccess(): array
    {
        $contactType = CrmContactType::create([
            'name' => 'Test Artist',
            'slug' => 'test-artist-' . uniqid(),
            'is_system' => false,
            'is_active' => true,
        ]);

        $contact = CrmContact::create([
            'crm_contact_type_id' => $contactType->id,
            'display_name' => 'Test Person',
            'is_active' => true,
        ]);

        $external = ExternalAccess::factory()->active()->create([
            'crm_contact_id' => $contact->id,
        ]);

        return ['contactType' => $contactType, 'contact' => $contact, 'external' => $external];
    }

    private function attachProperty(CrmProperty $property, CrmContactType $type): void
    {
        $property->contactTypes()->attach($type->id, [
            'sort_order' => 0,
            'is_required' => false,
            'show_in_list' => true,
            'is_filterable' => false,
        ]);
    }

    private function invokeShow(ExternalAccess $external): array
    {
        $this->app['auth']->guard('external')->login($external);

        $controller = $this->app->make(ExternalCrmController::class);
        $request = Request::create('/external/crm', 'GET');
        $request->setLaravelSession($this->app['session.store']);
        $request->setUserResolver(fn ($guard = null) => $guard === 'external' ? $external : null);

        $response = $controller->show($request);

        return $response->toResponse($request)->getOriginalContent()->getData()['page']['props'];
    }

    #[Test]
    public function confidential_property_groups_are_excluded(): void
    {
        ['contactType' => $type, 'contact' => $contact, 'external' => $external] = $this->makeContactWithExternalAccess();

        $publicGroup = CrmPropertyGroup::create([
            'name' => 'Public Group',
            'is_confidential' => false,
            'sort_order' => 1,
        ]);
        $secretGroup = CrmPropertyGroup::create([
            'name' => 'Secret Group',
            'is_confidential' => true,
            'sort_order' => 2,
        ]);

        $publicProperty = CrmProperty::create([
            'crm_property_group_id' => $publicGroup->id,
            'name' => 'Public Field',
            'type' => 'text',
            'sort_order' => 1,
        ]);
        $secretProperty = CrmProperty::create([
            'crm_property_group_id' => $secretGroup->id,
            'name' => 'Secret Field',
            'type' => 'text',
            'sort_order' => 1,
        ]);

        $this->attachProperty($publicProperty, $type);
        $this->attachProperty($secretProperty, $type);

        CrmPropertyValue::create([
            'crm_contact_id' => $contact->id,
            'crm_property_id' => $publicProperty->id,
            'value' => 'visible-value',
        ]);
        CrmPropertyValue::create([
            'crm_contact_id' => $contact->id,
            'crm_property_id' => $secretProperty->id,
            'value' => 'forbidden-value',
        ]);

        $props = $this->invokeShow($external);

        $names = array_column($props['groups'], 'name');

        $this->assertContains('Public Group', $names);
        $this->assertNotContains('Secret Group', $names);

        $serialized = json_encode($props['groups'], JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('forbidden-value', $serialized);
        $this->assertStringContainsString('visible-value', $serialized);
    }

    #[Test]
    public function only_groups_for_contact_type_are_returned(): void
    {
        ['contactType' => $myType, 'contact' => $contact, 'external' => $external] = $this->makeContactWithExternalAccess();

        $otherType = CrmContactType::create([
            'name' => 'Other Type',
            'slug' => 'other-type-' . uniqid(),
            'is_system' => false,
            'is_active' => true,
        ]);

        $mineGroup = CrmPropertyGroup::create([
            'name' => 'Mine Group',
            'is_confidential' => false,
            'sort_order' => 1,
        ]);
        $foreignGroup = CrmPropertyGroup::create([
            'name' => 'Foreign Group',
            'is_confidential' => false,
            'sort_order' => 2,
        ]);

        $mineProperty = CrmProperty::create([
            'crm_property_group_id' => $mineGroup->id,
            'name' => 'Mine Field',
            'type' => 'text',
            'sort_order' => 1,
        ]);
        $foreignProperty = CrmProperty::create([
            'crm_property_group_id' => $foreignGroup->id,
            'name' => 'Foreign Field',
            'type' => 'text',
            'sort_order' => 1,
        ]);

        $this->attachProperty($mineProperty, $myType);
        $this->attachProperty($foreignProperty, $otherType);

        $props = $this->invokeShow($external);

        $names = array_column($props['groups'], 'name');

        $this->assertContains('Mine Group', $names);
        $this->assertNotContains('Foreign Group', $names);
    }
}
