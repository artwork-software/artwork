<?php

namespace Tests\Unit\Modules\User\Services;

use Artwork\Modules\User\Services\UserCommentedBudgetItemsSettingService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserCommentedBudgetItemsSettingServiceTest extends TestCase
{
    #[Test]
    public function service_can_be_resolved_from_container(): void
    {
        $service = app(UserCommentedBudgetItemsSettingService::class);

        $this->assertInstanceOf(UserCommentedBudgetItemsSettingService::class, $service);
    }
}
