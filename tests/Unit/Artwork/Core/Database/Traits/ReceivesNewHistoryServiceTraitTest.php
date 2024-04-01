<?php

namespace Tests\Unit\Artwork\Core\Database\Traits;

use App\Support\Services\NewHistoryService;
use Artwork\Core\Database\Traits\ReceivesNewHistoryServiceTrait;
use Tests\TestCase;

class ReceivesNewHistoryServiceTraitTest extends TestCase
{

    public function testGetNewHistoryService(): void
    {
        $class = new class {
            use ReceivesNewHistoryServiceTrait;

            public function a()
            {
                return $this->getNewHistoryService('lel');
            }
        };

        $this->assertInstanceOf(NewHistoryService::class, $class->a());
    }
}
