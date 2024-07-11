<?php

namespace Tests\Unit\Artwork\Modules\InventoryScheduling\Http\Controller;

use Artwork\Modules\InventoryScheduling\Http\Controller\CraftInventoryItemEventController;
use Artwork\Modules\InventoryScheduling\Models\CraftInventoryItemEvent;
use Artwork\Modules\InventoryScheduling\Services\CraftInventoryItemEventService;
use Illuminate\Auth\AuthManager;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Throwable;

class CraftInventoryItemEventControllerTest extends TestCase
{
    private readonly AuthManager $authManagerMock;
    private readonly CraftInventoryItemEventService $craftInventoryItemEventServiceMock;

    /**
     * @throws Throwable
     */
    protected function setUp(): void
    {
        $this->authManagerMock = $this->getMockBuilder(AuthManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['guard', '__call'])
            ->getMock();

        $this->craftInventoryItemEventServiceMock = $this->getMockBuilder(CraftInventoryItemEventService::class)
            ->onlyMethods(['storeMultipleInventoryItemsInEvent', 'updateQuantity', 'dropItemToEvent'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getController(): CraftInventoryItemEventController
    {
        return new CraftInventoryItemEventController(
            $this->authManagerMock,
            $this->craftInventoryItemEventServiceMock
        );
    }


    /**
     * @return array<string, array<string, array<string, mixed>>>
     */
    public static function testUpdateDataProvider(): array
    {
        return [
            'test1' => [
                'data' => [
                    'quantity' => 1,
                ],
                'expected' => [
                    'quantity' => 1,
                ],
            ],
            'test2' => [
                'data' => [
                    'quantity' => 2,
                ],
                'expected' => [
                    'quantity' => 2,
                ],
            ],
        ];
    }

    /**
     * @dataProvider testUpdateDataProvider
     */
    public function testUpdate(
        array $data,
        array $expected
    ): void {
        $controller = $this->getController();

        $this->craftInventoryItemEventServiceMock->expects($this->once())
            ->method('updateQuantity')
            ->with($data)
            ->willReturn($expected);

        $craftInventoryItemEventMock = $this->getMockBuilder(CraftInventoryItemEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $controller->update($data, $craftInventoryItemEventMock);
    }
}
