<?php

namespace Tests\Unit\Pure\Enums\Notification;

use Artwork\Modules\Notification\Enums\NotificationGroupEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

final class NotificationGroupEnumTest extends UnitTestCase
{
    #[Test]
    public function it_has_events_case(): void
    {
        $this->assertSame('EVENTS', NotificationGroupEnum::EVENTS->value);
    }

    #[Test]
    public function it_has_budget_case(): void
    {
        $this->assertSame('BUDGET', NotificationGroupEnum::BUDGET->value);
    }

    #[Test]
    public function it_has_rooms_case(): void
    {
        $this->assertSame('ROOMS', NotificationGroupEnum::ROOMS->value);
    }

    #[Test]
    public function it_has_tasks_case(): void
    {
        $this->assertSame('TASKS', NotificationGroupEnum::TASKS->value);
    }

    #[Test]
    public function it_has_projects_case(): void
    {
        $this->assertSame('PROJECTS', NotificationGroupEnum::PROJECTS->value);
    }

    #[Test]
    public function it_has_shifts_case(): void
    {
        $this->assertSame('SHIFTS', NotificationGroupEnum::SHIFTS->value);
    }

    #[Test]
    public function it_has_inventory_case(): void
    {
        $this->assertSame('INVENTORY', NotificationGroupEnum::INVENTORY->value);
    }

    #[Test]
    public function it_has_documents_case(): void
    {
        $this->assertSame('DOCUMENTS', NotificationGroupEnum::DOCUMENTS->value);
    }

    #[Test]
    public function it_has_expected_total_cases(): void
    {
        $this->assertCount(8, NotificationGroupEnum::cases());
    }

    #[Test]
    public function events_title_is_correct(): void
    {
        $this->assertSame('Room assignments & events', NotificationGroupEnum::EVENTS->title());
    }

    #[Test]
    public function shifts_title_is_correct(): void
    {
        $this->assertSame('Shift Planning', NotificationGroupEnum::SHIFTS->title());
    }

    #[Test]
    public function all_titles_are_non_empty(): void
    {
        foreach (NotificationGroupEnum::cases() as $case) {
            $this->assertNotSame('', $case->title());
        }
    }

    #[Test]
    public function all_descriptions_are_non_empty(): void
    {
        foreach (NotificationGroupEnum::cases() as $case) {
            $this->assertNotSame('', $case->description());
        }
    }

    #[Test]
    public function try_from_returns_null_for_unknown(): void
    {
        $this->assertNull(NotificationGroupEnum::tryFrom('UNKNOWN'));
    }
}
