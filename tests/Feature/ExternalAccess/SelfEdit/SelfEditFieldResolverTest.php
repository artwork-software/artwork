<?php

namespace Tests\Feature\ExternalAccess\SelfEdit;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\ExternalAccess\Enums\SelfEditSectionMode;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Services\ExternalSelfEditFieldResolver;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SelfEditFieldResolverTest extends TestCase
{
    private function resolver(): ExternalSelfEditFieldResolver
    {
        return app(ExternalSelfEditFieldResolver::class);
    }

    private function externalForEntity(object $entity, CrmContactType $type): ExternalAccess
    {
        $entity->createCrmContact();
        $contact = $entity->crmContact()->firstOrFail();

        return ExternalAccess::factory()->create(['crm_contact_id' => $contact->id]);
    }

    private function attachProperty(CrmContactType $type, bool $confidential, bool $required = false): CrmProperty
    {
        $group = CrmPropertyGroup::query()->create([
            'name' => $confidential ? 'Vertraulich' : 'Allgemein',
            'is_confidential' => $confidential,
        ]);
        $property = CrmProperty::query()->create([
            'crm_property_group_id' => $group->id,
            'name' => $confidential ? 'IBAN' : 'Lieblingsfarbe',
            'type' => CrmPropertyTypeEnum::TEXT->value,
        ]);
        $property->contactTypes()->attach($type->id, ['is_required' => $required]);

        return $property;
    }

    #[Test]
    public function resolves_staged_personal_section_for_freelancer(): void
    {
        $type = CrmContactType::query()->create(['name' => 'Freelancer', 'slug' => 'freelancer']);
        $external = $this->externalForEntity(Freelancer::factory()->create(['zip_code' => '10115']), $type);

        $schema = $this->resolver()->resolveFor($external);
        $personal = collect($schema->sections)->firstWhere('key', 'personal');

        $this->assertNotNull($personal);
        $this->assertSame(SelfEditSectionMode::STAGED, $personal->mode);
        $keys = array_map(fn ($f) => $f->key, $personal->fields);
        $this->assertContains('zip_code', $keys);
        $this->assertContains('first_name', $keys);
    }

    #[Test]
    public function resolves_staged_personal_section_for_service_provider(): void
    {
        $type = CrmContactType::query()->create(['name' => 'Dienstleister', 'slug' => 'service_provider']);
        $external = $this->externalForEntity(ServiceProvider::factory()->create(), $type);

        $schema = $this->resolver()->resolveFor($external);
        $personal = collect($schema->sections)->firstWhere('key', 'personal');

        $this->assertNotNull($personal);
        $this->assertSame(SelfEditSectionMode::STAGED, $personal->mode);
        $this->assertContains('provider_name', array_map(fn ($f) => $f->key, $personal->fields));
    }

    #[Test]
    public function resolves_crm_property_sections_with_direct_mode(): void
    {
        $type = CrmContactType::query()->create(['name' => 'Freelancer', 'slug' => 'freelancer']);
        $this->attachProperty($type, confidential: false);
        $external = $this->externalForEntity(Freelancer::factory()->create(), $type);

        $schema = $this->resolver()->resolveFor($external);
        $direct = collect($schema->sections)->filter(fn ($s) => $s->mode === SelfEditSectionMode::DIRECT);

        $this->assertCount(1, $direct);
        $this->assertStringStartsWith('crm_property:', $direct->first()->fields[0]->key);
    }

    #[Test]
    public function excludes_confidential_property_groups(): void
    {
        $type = CrmContactType::query()->create(['name' => 'Freelancer', 'slug' => 'freelancer']);
        $this->attachProperty($type, confidential: true, required: true);
        $external = $this->externalForEntity(Freelancer::factory()->create(), $type);

        $schema = $this->resolver()->resolveFor($external);
        $direct = collect($schema->sections)->filter(fn ($s) => $s->mode === SelfEditSectionMode::DIRECT);

        $this->assertCount(0, $direct);
    }

    #[Test]
    public function does_not_include_salary_or_work_fields_for_freelancer(): void
    {
        $type = CrmContactType::query()->create(['name' => 'Freelancer', 'slug' => 'freelancer']);
        $external = $this->externalForEntity(Freelancer::factory()->create(), $type);

        $schema = $this->resolver()->resolveFor($external);
        $personal = collect($schema->sections)->firstWhere('key', 'personal');
        $keys = array_map(fn ($f) => $f->key, $personal->fields);

        foreach (['salary_per_hour', 'salary_description', 'can_work_shifts', 'work_name', 'work_description'] as $forbidden) {
            $this->assertNotContains($forbidden, $keys);
        }
    }

    #[Test]
    public function falls_back_to_crm_only_for_bare_crm_contact(): void
    {
        $type = CrmContactType::query()->create(['name' => 'Custom', 'slug' => 'custom_type']);
        $this->attachProperty($type, confidential: false);

        $contact = CrmContact::query()->create([
            'crm_contact_type_id' => $type->id,
            'display_name' => 'Bare Contact',
            'is_active' => true,
        ]);
        $external = ExternalAccess::factory()->create(['crm_contact_id' => $contact->id]);

        $schema = $this->resolver()->resolveFor($external);

        $this->assertNull(collect($schema->sections)->firstWhere('key', 'personal'));
        $this->assertCount(1, $schema->sections);
        $this->assertSame(SelfEditSectionMode::DIRECT, $schema->sections[0]->mode);
    }
}
