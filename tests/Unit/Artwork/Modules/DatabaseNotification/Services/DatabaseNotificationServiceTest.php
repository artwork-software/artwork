<?php

namespace Tests\Unit\Artwork\Modules\DatabaseNotification\Services;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Modules\DatabaseNotification\Repositories\DatabaseNotificationRepository;
use Artwork\Modules\DatabaseNotification\Services\DatabaseNotificationService;
use Illuminate\Notifications\DatabaseNotification;
use PHPUnit\Framework\TestCase;
use Throwable;

class DatabaseNotificationServiceTest extends TestCase
{
    private DatabaseNotificationRepository $databaseNotificationRepository;
    private CarbonService $carbonService;

    protected function setUp(): void
    {
        $this->databaseNotificationRepository = $this
            ->getMockBuilder(DatabaseNotificationRepository::class)
            ->onlyMethods(['updateOrFail'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->carbonService = $this->createMock(CarbonService::class);
    }

    public function getService(): DatabaseNotificationService
    {
        return new DatabaseNotificationService($this->databaseNotificationRepository, $this->carbonService);
    }

    /**
     * @return array<array<array<string, mixed>>>
     */
    public static function updateSentInSummaryTestDataProvider(): array
    {
        return [
            [
                [
                    'sent_in_summary' => true
                ]
            ]
        ];
    }

    /**
     * @dataProvider updateSentInSummaryTestDataProvider
     * @throws Throwable
     */
    public function testUpdateSentInSummary(array $attributes): void
    {
        $this->databaseNotificationRepository->expects($this->once())
            ->method('updateOrFail')
            ->with(
                $databaseNotificationStub = $this->createStub(DatabaseNotification::class),
                $attributes
            )
            ->willReturn($databaseNotificationStub);

        $this->getService()->updateSentInSummary($databaseNotificationStub, true);
    }
}
