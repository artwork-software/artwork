<?php

namespace Tests\Unit\Pure\Enums\Project;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

final class ProjectTabComponentEnumTest extends UnitTestCase
{
    #[Test]
    public function it_has_checkbox_case(): void
    {
        $this->assertSame('Checkbox', ProjectTabComponentEnum::CHECKBOX->value);
    }

    #[Test]
    public function it_has_text_field_case(): void
    {
        $this->assertSame('TextField', ProjectTabComponentEnum::TEXT_FIELD->value);
    }

    #[Test]
    public function it_has_dropdown_case(): void
    {
        $this->assertSame('DropDown', ProjectTabComponentEnum::DROPDOWN->value);
    }

    #[Test]
    public function it_has_calendar_case(): void
    {
        $this->assertSame('CalendarTab', ProjectTabComponentEnum::CALENDAR->value);
    }

    #[Test]
    public function it_has_separator_case(): void
    {
        $this->assertSame('SeparatorComponent', ProjectTabComponentEnum::SEPARATOR->value);
    }

    #[Test]
    public function it_has_link_list_case(): void
    {
        $this->assertSame('LinkList', ProjectTabComponentEnum::LINK_LIST->value);
    }

    #[Test]
    public function all_values_are_unique(): void
    {
        $values = array_map(static fn (ProjectTabComponentEnum $case): string => $case->value, ProjectTabComponentEnum::cases());
        $this->assertSame(count($values), count(array_unique($values)));
    }

    #[Test]
    public function get_values_includes_checkbox_entry(): void
    {
        $values = ProjectTabComponentEnum::getValues();
        $this->assertArrayHasKey('Checkbox', $values);
        $this->assertSame('Checkbox', $values['Checkbox']['name']);
        $this->assertArrayHasKey('availableFields', $values['Checkbox']);
    }

    #[Test]
    public function get_values_includes_link_list_entry_with_max_items(): void
    {
        $values = ProjectTabComponentEnum::getValues();
        $this->assertArrayHasKey('LinkList', $values);
        $this->assertSame(20, $values['LinkList']['availableFields']['max_items']);
    }

    #[Test]
    public function get_values_text_area_provides_label_text_and_placeholder(): void
    {
        $values = ProjectTabComponentEnum::getValues();
        $this->assertArrayHasKey('TextArea', $values);
        $this->assertArrayHasKey('label', $values['TextArea']['availableFields']);
        $this->assertArrayHasKey('text', $values['TextArea']['availableFields']);
        $this->assertArrayHasKey('placeholder', $values['TextArea']['availableFields']);
    }

    #[Test]
    public function try_from_returns_null_for_unknown(): void
    {
        $this->assertNull(ProjectTabComponentEnum::tryFrom('NonexistentComponent'));
    }
}
