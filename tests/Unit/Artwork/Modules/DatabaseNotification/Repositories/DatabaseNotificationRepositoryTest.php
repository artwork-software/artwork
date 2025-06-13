<?php

namespace Tests\Unit\Artwork\Modules\DatabaseNotification\Repositories;

use Artwork\Modules\Notification\Repositories\DatabaseNotificationRepository;
use Illuminate\Notifications\DatabaseNotification;
use PHPUnit\Framework\TestCase;
use Throwable;

class DatabaseNotificationRepositoryTest extends TestCase
{
    private DatabaseNotification $databaseNotification;

    protected function setUp(): void
    {
        $this->databaseNotification = $this->getMockBuilder(DatabaseNotification::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['updateOrFail'])
            ->getMock();
    }

    public function getRepository(): DatabaseNotificationRepository
    {
        return new DatabaseNotificationRepository($this->databaseNotification);
    }

    /**
     * @return array<array<array<string, mixed>>>
     */
    public static function updateOrFailTestDataProvider(): array
    {
        return [
            [
                [
                    'test' => 123
                ]
            ]
        ];
    }

    /**
     * @dataProvider updateOrFailTestDataProvider
     * @throws Throwable
     */
    public function testUpdateOrFail(array $attributes): void
    {
        $this->databaseNotification->expects($this->once())
            ->method('updateOrFail')
            ->with($attributes)
            ->willReturn(true);

        $this->getRepository()->updateOrFail($this->databaseNotification, $attributes);
    }
}
