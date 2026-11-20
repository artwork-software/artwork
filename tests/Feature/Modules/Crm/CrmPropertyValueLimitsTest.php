<?php

namespace Tests\Feature\Modules\Crm;

use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Crm\Support\CrmPropertyValueValidator;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Werteprüfung im CRM: Felder statt eines Werts und überlange Texte werden abgewiesen.
 */
final class CrmPropertyValueLimitsTest extends TestCase
{
    private function property(string $type): CrmProperty
    {
        $group = CrmPropertyGroup::query()->create([
            'name' => 'Grenzen ' . uniqid(),
            'is_confidential' => false,
            'sort_order' => 0,
            'is_system' => false,
        ]);

        return CrmProperty::query()->create([
            'crm_property_group_id' => $group->id,
            'name' => 'Grenze ' . uniqid(),
            'type' => $type,
            'is_system' => false,
            'sort_order' => 0,
        ]);
    }

    private function contactType(): CrmContactType
    {
        return CrmContactType::query()->create([
            'name' => 'Grenztyp ' . uniqid(),
            'slug' => 'grenztyp-' . uniqid(),
            'is_system' => false,
            'is_active' => true,
        ]);
    }

    /**
     * @param array<int, mixed> $values
     * @return array<int, string>
     */
    private function failingKeys(array $values): array
    {
        try {
            app(CrmPropertyValueValidator::class)->validate($this->contactType(), $values);
        } catch (ValidationException $exception) {
            return array_keys($exception->errors());
        }

        return [];
    }

    #[Test]
    public function an_array_instead_of_a_single_value_is_rejected(): void
    {
        $checkbox = $this->property('checkbox');
        $text = $this->property('text');

        $this->assertSame(
            ['property_values.' . $checkbox->id, 'property_values.' . $text->id],
            $this->failingKeys([$checkbox->id => ['x'], $text->id => []])
        );
    }

    #[Test]
    public function an_overlong_text_is_rejected_and_a_normal_one_passes(): void
    {
        $text = $this->property('text');

        $this->assertSame(
            ['property_values.' . $text->id],
            $this->failingKeys([$text->id => str_repeat('a', CrmPropertyValueValidator::MAX_LENGTH + 1)])
        );
        $this->assertSame([], $this->failingKeys([$text->id => str_repeat('a', 500)]));
        $this->assertSame([], $this->failingKeys([$text->id => null]));
    }
}
