<?php

namespace Tests\Unit\Pure\Enums\Role;

use Artwork\Modules\Role\Enums\RoleEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

final class RoleEnumTest extends UnitTestCase
{
    #[Test]
    public function it_has_artwork_admin_case(): void
    {
        $this->assertSame('artwork admin', RoleEnum::ARTWORK_ADMIN->value);
    }

    #[Test]
    public function it_has_budget_admin_case(): void
    {
        $this->assertSame('budget admin', RoleEnum::BUDGET_ADMIN->value);
    }

    #[Test]
    public function it_has_contract_admin_case(): void
    {
        $this->assertSame('contract admin', RoleEnum::CONTRACT_ADMIN->value);
    }

    #[Test]
    public function it_has_money_source_admin_case(): void
    {
        $this->assertSame('money source admin', RoleEnum::MONEY_SOURCE_ADMIN->value);
    }

    #[Test]
    public function it_has_room_admin_case(): void
    {
        $this->assertSame('room admin', RoleEnum::ROOM_ADMIN->value);
    }

    #[Test]
    public function it_has_user_case(): void
    {
        $this->assertSame('user', RoleEnum::USER->value);
    }

    #[Test]
    public function it_has_expected_total_cases(): void
    {
        $this->assertCount(6, RoleEnum::cases());
    }

    #[Test]
    public function try_from_returns_null_for_unknown(): void
    {
        $this->assertNull(RoleEnum::tryFrom('nonexistent_role'));
    }

    #[Test]
    public function from_value_returns_the_correct_case(): void
    {
        $this->assertSame(RoleEnum::ARTWORK_ADMIN, RoleEnum::from('artwork admin'));
    }
}
