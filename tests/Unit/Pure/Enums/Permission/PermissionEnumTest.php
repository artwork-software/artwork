<?php

namespace Tests\Unit\Pure\Enums\Permission;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

final class PermissionEnumTest extends UnitTestCase
{
    #[Test]
    public function it_has_project_management_case(): void
    {
        $this->assertSame('management projects', PermissionEnum::PROJECT_MANAGEMENT->value);
    }

    #[Test]
    public function it_has_project_delete_case(): void
    {
        $this->assertSame('delete projects', PermissionEnum::PROJECT_DELETE->value);
    }

    #[Test]
    public function it_has_project_view_case(): void
    {
        $this->assertSame('view projects', PermissionEnum::PROJECT_VIEW->value);
    }

    #[Test]
    public function it_has_write_projects_case(): void
    {
        $this->assertSame('write projects', PermissionEnum::WRITE_PROJECTS->value);
    }

    #[Test]
    public function it_has_event_request_case(): void
    {
        $this->assertSame('request room occupancy', PermissionEnum::EVENT_REQUEST->value);
    }

    #[Test]
    public function it_has_inventory_settings_case(): void
    {
        $this->assertSame('inventory.settings', PermissionEnum::INVENTORY_SETTINGS->value);
    }

    #[Test]
    public function it_has_crm_view_case(): void
    {
        $this->assertSame('can view crm', PermissionEnum::CRM_VIEW->value);
    }

    #[Test]
    public function it_has_crm_manager_case(): void
    {
        $this->assertSame('crm manager', PermissionEnum::CRM_MANAGER->value);
    }

    #[Test]
    public function all_values_are_unique(): void
    {
        $values = array_map(static fn (PermissionEnum $case): string => $case->value, PermissionEnum::cases());
        $this->assertSame(count($values), count(array_unique($values)));
    }

    #[Test]
    public function it_has_more_than_50_cases(): void
    {
        $this->assertGreaterThan(50, count(PermissionEnum::cases()));
    }

    #[Test]
    public function try_from_returns_null_for_unknown(): void
    {
        $this->assertNull(PermissionEnum::tryFrom('totally invented permission'));
    }

    #[Test]
    public function from_value_returns_the_correct_case(): void
    {
        $this->assertSame(PermissionEnum::PROJECT_VIEW, PermissionEnum::from('view projects'));
    }
}
