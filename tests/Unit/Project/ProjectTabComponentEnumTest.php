<?php

namespace Tests\Unit\Project;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

final class ProjectTabComponentEnumTest extends UnitTestCase
{
    #[Test]
    public function text_field_is_externally_readable_and_writable(): void
    {
        $this->assertTrue(ProjectTabComponentEnum::TEXT_FIELD->isExternallyReadable());
        $this->assertTrue(ProjectTabComponentEnum::TEXT_FIELD->isExternallyWritable());
    }

    #[Test]
    public function title_is_readable_but_not_writable(): void
    {
        $this->assertTrue(ProjectTabComponentEnum::TITLE->isExternallyReadable());
        $this->assertFalse(ProjectTabComponentEnum::TITLE->isExternallyWritable());
    }

    #[Test]
    public function budget_is_neither_readable_nor_writable(): void
    {
        $this->assertFalse(ProjectTabComponentEnum::BUDGET->isExternallyReadable());
        $this->assertFalse(ProjectTabComponentEnum::BUDGET->isExternallyWritable());
    }

    #[Test]
    public function project_team_is_neither_readable_nor_writable(): void
    {
        $this->assertFalse(ProjectTabComponentEnum::PROJECT_TEAM->isExternallyReadable());
        $this->assertFalse(ProjectTabComponentEnum::PROJECT_TEAM->isExternallyWritable());
    }
}
