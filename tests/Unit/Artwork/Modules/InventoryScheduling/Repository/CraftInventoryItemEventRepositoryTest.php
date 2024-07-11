<?php

namespace Tests\Unit\Artwork\Modules\InventoryScheduling\Repository;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryScheduling\Models\CraftInventoryItemEvent;
use Artwork\Modules\InventoryScheduling\Repositories\CraftInventoryItemEventRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Throwable;
use function Symfony\Component\Translation\t;

class CraftInventoryItemEventRepositoryTest extends TestCase
{
    private readonly CraftInventoryItemEvent $craftInventoryItemEventMock;

    private readonly BaseBuilder $baseBuilderMock;

    /**
     * @throws Throwable
     */
    protected function setUp(): void
    {
        $this->craftInventoryItemEventMock = $this->getMockBuilder(CraftInventoryItemEvent::class)
            ->onlyMethods(['newModelQuery', 'newInstance'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->baseBuilderMock = $this->createMock(BaseBuilder::class);
    }

    private function getRepository(): CraftInventoryItemEventRepository
    {
        return new CraftInventoryItemEventRepository(
            $this->craftInventoryItemEventMock
        );
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    public static function getNewModelInstanceTestDataProvider(): array
    {
        return [
            'test with empty attributes' => [
                [],
            ]
        ];
    }

    /**
     * @dataProvider getNewModelInstanceTestDataProvider
     */
    public function testGetNewModelInstance(
        $expectedAttributes
    ): void {
        $this->craftInventoryItemEventMock->expects(self::once())
            ->method('newInstance')
            ->with($expectedAttributes)
            ->willReturnSelf();

        $this->getRepository()->getNewModelInstance($expectedAttributes);
    }

    /**
     * @throws Throwable
     */
    public function testGetNewModelQuery(): void
    {
        $this->craftInventoryItemEventMock->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($this->createStub(Builder::class));

        $this->getRepository()->getNewModelQuery();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function findTestDataProvider(): array
    {
        return [
            'test return model' => [
                1,
                new CraftInventoryItemEvent()
            ],
            'test return null' => [
                1,
                null
            ]
        ];
    }

    /**
     * @dataProvider findTestDataProvider
     * @throws Throwable
     */
    public function testFindEventByEventId(
        int $expectedId,
        CraftInventoryItemEvent|null $expectedFindReturn
    ): void {
        $this->baseBuilderMock->expects($this->once())
            ->method('where')
            ->with('event_id', $expectedId)
            ->willReturnSelf();

        $this->baseBuilderMock->expects($this->once())
            ->method('first')
            ->willReturn($expectedFindReturn);

        $this->craftInventoryItemEventMock->expects($this->once())
            ->method('newModelQuery')
            ->willReturn($this->baseBuilderMock);

        $this->assertSame(
            $expectedFindReturn,
            $this->getRepository()->findEventByEventId($expectedId)
        );
    }
}
