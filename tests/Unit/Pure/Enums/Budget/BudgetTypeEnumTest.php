<?php

namespace Tests\Unit\Pure\Enums\Budget;

use Artwork\Modules\Budget\Enums\BudgetTypeEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

final class BudgetTypeEnumTest extends UnitTestCase
{
    #[Test]
    public function it_has_cost_case(): void
    {
        $this->assertSame('BUDGET_TYPE_COST', BudgetTypeEnum::BUDGET_TYPE_COST->value);
    }

    #[Test]
    public function it_has_earning_case(): void
    {
        $this->assertSame('BUDGET_TYPE_EARNING', BudgetTypeEnum::BUDGET_TYPE_EARNING->value);
    }

    #[Test]
    public function it_has_verified_not_verified_case(): void
    {
        $this->assertSame('BUDGET_VERIFIED_TYPE_NOT_VERIFIED', BudgetTypeEnum::BUDGET_VERIFIED_TYPE_NOT_VERIFIED->value);
    }

    #[Test]
    public function it_has_verified_closed_case(): void
    {
        $this->assertSame('BUDGET_VERIFIED_TYPE_CLOSED', BudgetTypeEnum::BUDGET_VERIFIED_TYPE_CLOSED->value);
    }

    #[Test]
    public function it_has_verified_requested_case(): void
    {
        $this->assertSame('BUDGET_VERIFIED_TYPE_REQUESTED', BudgetTypeEnum::BUDGET_VERIFIED_TYPE_REQUESTED->value);
    }

    #[Test]
    public function it_has_verification_deleted_case(): void
    {
        $this->assertSame('BUDGET_VERIFICATION_DELETED', BudgetTypeEnum::BUDGET_VERIFICATION_DELETED->value);
    }

    #[Test]
    public function it_has_verification_take_back_case(): void
    {
        $this->assertSame('BUDGET_VERIFICATION_TAKE_BACK', BudgetTypeEnum::BUDGET_VERIFICATION_TAKE_BACK->value);
    }

    #[Test]
    public function it_has_verification_request_case(): void
    {
        $this->assertSame('BUDGET_VERIFICATION_REQUEST', BudgetTypeEnum::BUDGET_VERIFICATION_REQUEST->value);
    }

    #[Test]
    public function it_has_eight_cases(): void
    {
        $this->assertCount(8, BudgetTypeEnum::cases());
    }

    #[Test]
    public function try_from_returns_null_for_unknown(): void
    {
        $this->assertNull(BudgetTypeEnum::tryFrom('UNKNOWN_BUDGET_TYPE'));
    }
}
