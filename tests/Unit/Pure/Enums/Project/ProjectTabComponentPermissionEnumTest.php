<?php

namespace Tests\Unit\Pure\Enums\Project;

use Artwork\Modules\Project\Enum\ProjectTabComponentPermissionEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

final class ProjectTabComponentPermissionEnumTest extends UnitTestCase
{
    #[Test]
    public function it_has_all_see_and_edit_case(): void
    {
        $this->assertSame(
            'allSeeAndEdit',
            ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
        );
    }

    #[Test]
    public function it_has_all_see_some_edit_case(): void
    {
        $this->assertSame(
            'allSeeSomeEdit',
            ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_SOME_EDIT->value
        );
    }

    #[Test]
    public function it_has_some_see_some_edit_case(): void
    {
        $this->assertSame(
            'someSeeSomeEdit',
            ProjectTabComponentPermissionEnum::PERMISSION_TYPE_SOME_SEE_SOME_EDIT->value
        );
    }

    #[Test]
    public function it_has_three_cases(): void
    {
        $this->assertCount(3, ProjectTabComponentPermissionEnum::cases());
    }

    #[Test]
    public function try_from_returns_null_for_unknown(): void
    {
        $this->assertNull(ProjectTabComponentPermissionEnum::tryFrom('allSeeAllEdit'));
    }
}
