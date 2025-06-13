<?php

namespace Tests\Unit\Artwork\Core\Console\Commands;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Core\Console\Commands\SendNotificationsEmailSummariesCommand;
use Artwork\Modules\Notification\Services\DatabaseNotificationService;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Enums\NotificationFrequencyEnum;
use Artwork\Modules\Notification\Models\NotificationSetting;
use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use AssertionError;
use Carbon\Carbon;
use Illuminate\Config\Repository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\MailManager;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class SendNotificationsEmailSummariesCommandTest extends TestCase
{
    private readonly LoggerInterface $loggerMock;

    private readonly GeneralSettings $generalSettingsMock;

    private readonly UserService $userServiceMock;

    private readonly DatabaseNotificationService $databaseNotificationServiceMock;

    private readonly Repository $configMock;

    private readonly NotificationSettingService $notificationSettingServiceMock;

    private readonly User $userMock;

    private readonly NotificationSetting $notificationSettingMock;

    private readonly Collection $collectionMock;

    private readonly CarbonService $carbonServiceMock;

    private readonly MailManager $mailManagerMock;

    private readonly Translator $translatorMock;

    private readonly SendNotificationsEmailSummariesCommand $sendNotificationsEmailSummariesCommand;

    public function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(NullLogger::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->generalSettingsMock = $this->getMockBuilder(GeneralSettings::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->userServiceMock = $this->getMockBuilder(UserService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllUsers', 'getNotReadOfNotificationTypeNotSentInSummaryForUser'])
            ->getMock();

        $this->databaseNotificationServiceMock = $this->getMockBuilder(DatabaseNotificationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->configMock = $this->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->notificationSettingServiceMock = $this->getMockBuilder(NotificationSettingService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEnabledOfUser'])
            ->getMock();

        $this->userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
            ->getMock();

        $this->notificationSettingMock = $this->getMockBuilder(NotificationSetting::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
            ->getMock();

        $this->collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['groupBy', 'values', 'count'])
            ->getMock();

        $this->carbonServiceMock = $this->getMockBuilder(CarbonService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['parseAndAddDay', 'parseAndAddThreeDays', 'parseAndAddWeek', 'getTodayMidnight'])
            ->getMock();

        $this->mailManagerMock = $this->getMockBuilder(MailManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->translatorMock = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->sendNotificationsEmailSummariesCommand = new class (
            $this->loggerMock,
            $this->generalSettingsMock,
            $this->userServiceMock,
            $this->databaseNotificationServiceMock,
            $this->configMock,
            $this->notificationSettingServiceMock,
            $this->carbonServiceMock,
            $this->mailManagerMock,
            $this->translatorMock
        ) extends SendNotificationsEmailSummariesCommand {
            protected function sendNotificationsSummary(User $user): void
            {
            }

            /**
             * @return array<string, array<string, array<int, DatabaseNotification>>>
             */
            public function callCollectNotificationsToSendForUser(User $user): array
            {
                return $this->collectNotificationsToSendForUser($user);
            }
        };
    }

    /**
     * @return array<array<int|string,mixed>>
     */
    public static function collectNotificationsToSendForUserDataProvider(): array
    {
        return [
            'test daily' => [
                [
                    'id',
                    'notification_enums_last_sent_dates'
                ],
                [
                    1,
                    [
                        'ROOM_REQUEST' => Carbon::yesterday()->setTime(9, 0)->format('Y-m-d')
                    ]
                ],
                'collectionMock',
                'group_type',
                [
                    'type',
                    'frequency',
                    'group_type'
                ],
                [
                    NotificationEnum::NOTIFICATION_ROOM_REQUEST,
                    NotificationFrequencyEnum::DAILY,
                    NotificationEnum::NOTIFICATION_ROOM_REQUEST->value
                ],
                NotificationEnum::NOTIFICATION_ROOM_REQUEST->value,
                'collectionMock',
                1,
                'parseAndAddDay',
                Carbon::yesterday()->setTime(9, 0),
                Carbon::now()->setTime(0, 0),
                [
                    NotificationEnum::NOTIFICATION_ROOM_REQUEST->value => [
                        NotificationEnum::NOTIFICATION_ROOM_REQUEST->value => 'collectionMock'
                    ]
                ]
            ],
            'test weekly twice' => [
                [
                    'id',
                    'notification_enums_last_sent_dates'
                ],
                [
                    1,
                    [
                        'ROOM_REQUEST' => Carbon::yesterday()->setTime(9, 0)->format('Y-m-d')
                    ]
                ],
                'collectionMock',
                'group_type',
                [
                    'type',
                    'frequency',
                    'group_type'
                ],
                [
                    NotificationEnum::NOTIFICATION_ROOM_REQUEST,
                    NotificationFrequencyEnum::WEEKLY_TWICE,
                    NotificationEnum::NOTIFICATION_ROOM_REQUEST->value
                ],
                NotificationEnum::NOTIFICATION_ROOM_REQUEST->value,
                'collectionMock',
                1,
                'parseAndAddThreeDays',
                Carbon::now()->subDays(3)->setTime(9, 0),
                Carbon::now()->setTime(0, 0),
                [
                    NotificationEnum::NOTIFICATION_ROOM_REQUEST->value => [
                        NotificationEnum::NOTIFICATION_ROOM_REQUEST->value => 'collectionMock'
                    ]
                ]
            ],
            'test weekly once' => [
                [
                    'id',
                    'notification_enums_last_sent_dates'
                ],
                [
                    1,
                    [
                        'ROOM_REQUEST' => Carbon::yesterday()->setTime(9, 0)->format('Y-m-d')
                    ]
                ],
                'collectionMock',
                'group_type',
                [
                    'type',
                    'frequency',
                    'group_type'
                ],
                [
                    NotificationEnum::NOTIFICATION_ROOM_REQUEST,
                    NotificationFrequencyEnum::WEEKLY_ONCE,
                    NotificationEnum::NOTIFICATION_ROOM_REQUEST->value
                ],
                NotificationEnum::NOTIFICATION_ROOM_REQUEST->value,
                'collectionMock',
                1,
                'parseAndAddWeek',
                Carbon::now()->subWeek()->setTime(9, 0),
                Carbon::now()->setTime(0, 0),
                [
                    NotificationEnum::NOTIFICATION_ROOM_REQUEST->value => [
                        NotificationEnum::NOTIFICATION_ROOM_REQUEST->value => 'collectionMock'
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider collectNotificationsToSendForUserDataProvider
     */
    public function testCollectNotificationsToSendForUser(
        array $expectedUserGetAttributeKeys,
        array $expectedUserGetAttributeReturnValues,
        string $expectedGetEnabledOfUserReturnValue,
        string $expectedCollectionMockGroupByColumn,
        array $expectedNotificationSettingGetAttributeKeys,
        array $expectedNotificationSettingGetAttributeReturnValues,
        string $expectedUserServiceGetNotReadOfNotificationTypeNotSentInSummaryForUserValue,
        string $expectedUserServiceGetNotReadOfNotificationTypeNotSentInSummaryForUserReturnValue,
        int $expectedNotificationsCountReturnValue,
        string $expectedParseMethodDependingOnNotificationFrequencyEnum,
        Carbon $expectedParseAndAddDayReturnValue,
        Carbon $expectedGetTodayMidnightReturnValue,
        array $expectedResult
    ): void {
        $this->userMock->expects($matcher = $this->exactly(2))
            ->method('getAttribute')
            ->willReturnCallback(
                function ($key) use (
                    $matcher,
                    $expectedUserGetAttributeKeys,
                    $expectedUserGetAttributeReturnValues
                ): int|array {
                    switch (($invocationCount = $matcher->numberOfInvocations())) {
                        case 1:
                        case 2:
                            $this->assertSame($expectedUserGetAttributeKeys[($invocationCount - 1)], $key);

                            return $expectedUserGetAttributeReturnValues[($invocationCount - 1)];
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->notificationSettingServiceMock->expects($this->once())
            ->method('getEnabledOfUser')
            ->with($expectedUserGetAttributeReturnValues[0])
            ->willReturn($this->{$expectedGetEnabledOfUserReturnValue});

        $this->collectionMock->expects($this->once())
            ->method('groupBy')
            ->with($expectedCollectionMockGroupByColumn)
            ->willReturnSelf();

        $this->collectionMock->expects($this->once())
            ->method('values')
            ->willReturn([[$this->notificationSettingMock]]);

        $this->notificationSettingMock->expects(($matcher = $this->exactly(3)))
            ->method('getAttribute')
            ->willReturnCallback(
                function ($key) use (
                    $matcher,
                    $expectedNotificationSettingGetAttributeKeys,
                    $expectedNotificationSettingGetAttributeReturnValues
                ): NotificationFrequencyEnum|NotificationEnum|string {
                    switch (($invocationCount = $matcher->numberOfInvocations())) {
                        case 1:
                        case 2:
                        case 3:
                            $this->assertSame(
                                $expectedNotificationSettingGetAttributeKeys[($invocationCount - 1)],
                                $key
                            );

                            return $expectedNotificationSettingGetAttributeReturnValues[($invocationCount - 1)];
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->carbonServiceMock->expects($this->once())
            ->method($expectedParseMethodDependingOnNotificationFrequencyEnum)
            ->willReturn($expectedParseAndAddDayReturnValue);

        $this->carbonServiceMock->expects($this->once())
            ->method('getTodayMidnight')
            ->willReturn($expectedGetTodayMidnightReturnValue);

        $this->userServiceMock->expects($this->once())
            ->method('getNotReadOfNotificationTypeNotSentInSummaryForUser')
            ->with($this->userMock, $expectedUserServiceGetNotReadOfNotificationTypeNotSentInSummaryForUserValue)
            ->willReturn($this->{$expectedUserServiceGetNotReadOfNotificationTypeNotSentInSummaryForUserReturnValue});

        $this->collectionMock->expects($this->once())
            ->method('count')
            ->willReturn($expectedNotificationsCountReturnValue);

        self::assertSame(
            $this->getExpectedResultWithMocks($expectedResult),
            $this->sendNotificationsEmailSummariesCommand->callCollectNotificationsToSendForUser($this->userMock)
        );
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function getExpectedResultWithMocks(array $expectedResult): array
    {
        foreach ($expectedResult as $groupType => $notificationsByType) {
            foreach ($notificationsByType as $type => $couldBeMock) {
                if (str_contains($couldBeMock, 'Mock')) {
                    $expectedResult[$groupType][$type] = $this->{$couldBeMock};
                }
            }
        }

        return $expectedResult;
    }

    /**
     * @return array<array<int|string,mixed>>
     */
    public static function collectNotificationsToSendForUserSendSummaryFalseByLastDateDataProvider(): array
    {
        return [
            'test daily' => [
                [
                    'id',
                    'notification_enums_last_sent_dates'
                ],
                [
                    1,
                    [
                        'ROOM_REQUEST' => Carbon::yesterday()->setTime(9, 0)->format('Y-m-d')
                    ]
                ],
                'collectionMock',
                'group_type',
                [
                    'type',
                    'frequency'
                ],
                [
                    NotificationEnum::NOTIFICATION_ROOM_REQUEST,
                    NotificationFrequencyEnum::DAILY
                ],
                'parseAndAddDay',
                Carbon::today()->setTime(9, 0),
                Carbon::now()->setTime(0, 0),
                []
            ],
            'test weekly twice' => [
                [
                    'id',
                    'notification_enums_last_sent_dates'
                ],
                [
                    1,
                    [
                        'ROOM_REQUEST' => Carbon::yesterday()->setTime(9, 0)->format('Y-m-d')
                    ]
                ],
                'collectionMock',
                'group_type',
                [
                    'type',
                    'frequency'
                ],
                [
                    NotificationEnum::NOTIFICATION_ROOM_REQUEST,
                    NotificationFrequencyEnum::WEEKLY_TWICE
                ],
                'parseAndAddThreeDays',
                Carbon::today()->setTime(9, 0),
                Carbon::now()->setTime(0, 0),
                []
            ],
            'test weekly once' => [
                [
                    'id',
                    'notification_enums_last_sent_dates'
                ],
                [
                    1,
                    [
                        'ROOM_REQUEST' => Carbon::yesterday()->setTime(9, 0)->format('Y-m-d')
                    ]
                ],
                'collectionMock',
                'group_type',
                [
                    'type',
                    'frequency'
                ],
                [
                    NotificationEnum::NOTIFICATION_ROOM_REQUEST,
                    NotificationFrequencyEnum::WEEKLY_ONCE
                ],
                'parseAndAddWeek',
                Carbon::today()->setTime(9, 0),
                Carbon::now()->setTime(0, 0),
                []
            ]
        ];
    }

    /**
     * @dataProvider collectNotificationsToSendForUserSendSummaryFalseByLastDateDataProvider
     */
    public function testCollectNotificationsToSendForUserSendSummaryFalseByLastDate(
        array $expectedUserGetAttributeKeys,
        array $expectedUserGetAttributeReturnValues,
        string $expectedGetEnabledOfUserReturnValue,
        string $expectedCollectionMockGroupByColumn,
        array $expectedNotificationSettingGetAttributeKeys,
        array $expectedNotificationSettingGetAttributeReturnValues,
        string $expectedParseMethodDependingOnNotificationFrequencyEnum,
        Carbon $expectedParseAndAddDayReturnValue,
        Carbon $expectedGetTodayMidnightReturnValue,
        array $expectedResult
    ): void {
        $this->userMock->expects($matcher = $this->exactly(2))
            ->method('getAttribute')
            ->willReturnCallback(
                function ($key) use (
                    $matcher,
                    $expectedUserGetAttributeKeys,
                    $expectedUserGetAttributeReturnValues
                ): int|array {
                    switch (($invocationCount = $matcher->numberOfInvocations())) {
                        case 1:
                        case 2:
                            $this->assertSame($expectedUserGetAttributeKeys[($invocationCount - 1)], $key);

                            return $expectedUserGetAttributeReturnValues[($invocationCount - 1)];
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->notificationSettingServiceMock->expects($this->once())
            ->method('getEnabledOfUser')
            ->with($expectedUserGetAttributeReturnValues[0])
            ->willReturn($this->{$expectedGetEnabledOfUserReturnValue});

        $this->collectionMock->expects($this->once())
            ->method('groupBy')
            ->with($expectedCollectionMockGroupByColumn)
            ->willReturnSelf();

        $this->collectionMock->expects($this->once())
            ->method('values')
            ->willReturn([[$this->notificationSettingMock]]);

        $this->notificationSettingMock->expects(($matcher = $this->exactly(2)))
            ->method('getAttribute')
            ->willReturnCallback(
                function ($key) use (
                    $matcher,
                    $expectedNotificationSettingGetAttributeKeys,
                    $expectedNotificationSettingGetAttributeReturnValues
                ): NotificationFrequencyEnum|NotificationEnum {
                    switch (($invocationCount = $matcher->numberOfInvocations())) {
                        case 1:
                        case 2:
                            $this->assertSame(
                                $expectedNotificationSettingGetAttributeKeys[($invocationCount - 1)],
                                $key
                            );

                            return $expectedNotificationSettingGetAttributeReturnValues[($invocationCount - 1)];
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->carbonServiceMock->expects($this->once())
            ->method($expectedParseMethodDependingOnNotificationFrequencyEnum)
            ->willReturn($expectedParseAndAddDayReturnValue);

        $this->carbonServiceMock->expects($this->once())
            ->method('getTodayMidnight')
            ->willReturn($expectedGetTodayMidnightReturnValue);

        $this->userServiceMock->expects($this->never())
            ->method('getNotReadOfNotificationTypeNotSentInSummaryForUser');

        self::assertSame(
            $expectedResult,
            $this->sendNotificationsEmailSummariesCommand->callCollectNotificationsToSendForUser($this->userMock)
        );
    }
}
