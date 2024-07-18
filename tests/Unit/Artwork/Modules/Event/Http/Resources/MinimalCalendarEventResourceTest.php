<?php

namespace Tests\Unit\Artwork\Modules\Event\Http\Resources;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Http\Resources\MinimalCalendarEventResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectState;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use AssertionError;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Throwable;

class MinimalCalendarEventResourceTest extends TestCase
{
    private Event $eventMock;

    private Carbon $carbonMock;

    private Project $projectMock;

    private User $creatorMock;

    private Collection $managerUsersCollectionMock;

    private ProjectState $projectStateMock;

    private User $managerUserMock;

    private EventType $eventTypeMock;

    private Collection $shiftsCollectionMock;

    private Shift $shiftMock;

    private Craft $craftMock;

    private Collection $shiftRelationCollectionMock;

    private Collection $shiftsQualificationsCollectionMock;

    protected function setUp(): void
    {
        $this->eventMock = $this->getMockBuilder(Event::class)
            ->onlyMethods(['getAttribute'])
            ->disableAutoReturnValueGeneration()
            ->disableOriginalConstructor()
            ->getMock();

        $this->carbonMock = $this->getMockBuilder(Carbon::class)
            ->onlyMethods(['utc', 'toIso8601String'])
            ->disableAutoReturnValueGeneration()
            ->getMock();

        $this->projectMock = $this->getMockBuilder(Project::class)
            ->onlyMethods(['getAttribute', 'getRelation'])
            ->disableAutoReturnValueGeneration()
            ->disableOriginalConstructor()
            ->getMock();

        $this->creatorMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['getAttribute'])
            ->disableAutoReturnValueGeneration()
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerUsersCollectionMock = $this->getMockBuilder(Collection::class)
            ->onlyMethods(['all'])
            ->disableAutoReturnValueGeneration()
            ->disableOriginalConstructor()
            ->getMock();

        $this->projectStateMock = $this->getMockBuilder(ProjectState::class)
            ->onlyMethods(['getAttribute'])
            ->disableAutoReturnValueGeneration()
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerUserMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['getAttribute'])
            ->disableAutoReturnValueGeneration()
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventTypeMock = $this->getMockBuilder(EventType::class)
            ->onlyMethods(['getAttribute'])
            ->disableAutoReturnValueGeneration()
            ->disableOriginalConstructor()
            ->getMock();

        $this->shiftsCollectionMock = $this->getMockBuilder(Collection::class)
            ->onlyMethods(['all'])
            ->disableAutoReturnValueGeneration()
            ->disableOriginalConstructor()
            ->getMock();

        $this->shiftMock = $this->getMockBuilder(Shift::class)
            ->onlyMethods(['getAttribute'])
            ->disableAutoReturnValueGeneration()
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftMock = $this->getMockBuilder(Craft::class)
            ->onlyMethods(['getAttribute'])
            ->disableAutoReturnValueGeneration()
            ->disableOriginalConstructor()
            ->getMock();

        $this->shiftRelationCollectionMock = $this->getMockBuilder(Collection::class)
            ->onlyMethods(['count'])
            ->disableAutoReturnValueGeneration()
            ->disableOriginalConstructor()
            ->getMock();

        $this->shiftsQualificationsCollectionMock = $this->getMockBuilder(Collection::class)
            ->onlyMethods(['sum'])
            ->disableAutoReturnValueGeneration()
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getResource(): MinimalCalendarEventResource
    {
        app()->bind(
            'request',
            fn() => Request::create('/')
        );

        return new MinimalCalendarEventResource($this->eventMock);
    }

    /**
     * @return array<int, mixed>
     */
    public static function toArrayTestDataProvider(): array
    {
        return [
            'test to array with project' => [
                //expected event getAttribute invocation count
                14,
                //expected event getAttribute keys
                [
                    'project_id',
                    'project',
                    'creator',
                    'event_type',
                    'eventName',
                    'start_time',
                    'id',
                    'room_id',
                    'end_time',
                    'allDay',
                    'audience',
                    'is_loud',
                    'subEvents',
                    'shifts'
                ],
                //expected event getAttribute returns
                [
                    1,
                    'projectMock',
                    'creatorMock',
                    'eventTypeMock',
                    'Meeting Rock & Wrestling',
                    'carbonMock',
                    2,
                    3,
                    'carbonMock',
                    true,
                    false,
                    false,
                    [],
                    'shiftsCollectionMock'
                ],
                //expected project getAttribute invocation count
                2,
                //expected project getAttribute keys
                [
                    'name',
                    'managerUsers'
                ],
                //expected project getAttribute returns
                [
                    'Walid Raad',
                    'managerUsersCollectionMock'
                ],
                'state',
                //expected managerUsersCollectionMock all return values
                [
                    'managerUserMock',
                    'managerUserMock',
                    'managerUserMock'
                ],
                //expected ProjectState getAttribute key
                'color',
                //expected ProjectState getAttribute return
                '#3E0717',
                //expected managerUserMock getAttribute invocation count
                9,
                //expected managerUserMock getAttribute keys
                [
                    'profile_photo_url',
                    'first_name',
                    'last_name',
                ],
                //expected managerUserMock getAttribute returns
                [
                    '/profile-photos/photo-1499996860823-5214fcc65f8f.jpg',
                    'Max',
                    'Müller'
                ],
                //expected carbon mock utc invocation count
                2,
                //expected carbon mock toIso8601String invocation count,
                2,
                //expected carbon mock toIso8601String returns
                [
                    '2024-07-01T21:59:59+00:00',
                    '2024-07-02T21:59:59+00:00'
                ],
                4,
                //expected eventTypeMock getAttribute keys
                [
                    'hex_code',
                    'hex_code',
                    'name',
                    'abbreviation'
                ],
                //expected eventTypeMock getAttribute returns
                [
                    '#A7A6B1',
                    '#A7A6B1',
                    'Blocker',
                    'BL'
                ],
                //expected shiftsCollectionMock all return values
                [
                    'shiftMock',
                    'shiftMock',
                    'shiftMock'
                ],
                //expected shiftMock getAttribute invocation count
                24,
                //expected shiftMock getAttribute keys
                [
                    'id',
                    'craft',
                    'craft',
                    'craft',
                    'users',
                    'freelancer',
                    'serviceProvider',
                    'shiftsQualifications'
                ],
                //expected shiftMock getAttribute returns
                [
                    1,
                    'craftMock',
                    'craftMock',
                    'craftMock',
                    'shiftRelationCollectionMock',
                    'shiftRelationCollectionMock',
                    'shiftRelationCollectionMock',
                    'shiftsQualificationsCollectionMock'
                ],
                //expected craftMock getAttribute invocation count
                9,
                //expected craftMock getAttribute keys
                [
                    'id',
                    'name',
                    'abbreviation'
                ],
                //expected craftMock getAttribute returns
                [
                    1,
                    'Caldero-Systems GmbH',
                    'CS'
                ],
                //expected shiftRelationCollection count invocation count
                9,
                //expected shiftRelationCollectionMock count return value
                10,
                //expected shiftsQualificationsCollectionMock sum invocation count
                3,
                //expected shiftsQualificationsCollectionMock sum key
                'value',
                //expected shiftsQualificationsCollectionMock sum return
                35,
                //expected creatorMock getAttribute invocation count
                4,
                //expected creatorMock getAttribute keys
                [
                    'id',
                    'profile_photo_url',
                    'first_name',
                    'last_name'
                ],
                //expected creatorMock getAttribute returns
                [
                    1,
                    '/profile-photos/photo-1499996860823-5214fcc65f8f.jpg',
                    'Max',
                    'Müller'
                ],
                //expected result
                [
                    'id' => 2,
                    'projectId' => 1,
                    'roomId' => 3,
                    'created_by' => [
                        'id' => 1,
                        'profile_photo_url' => '/profile-photos/photo-1499996860823-5214fcc65f8f.jpg',
                        'first_name' => 'Max',
                        'last_name' => 'Müller'
                    ],
                    'start' => "2024-07-01T21:59:59+00:00",
                    'startTime' => 'carbonMock',
                    'end' => '2024-07-02T21:59:59+00:00',
                    'allDay' => true,
                    'alwaysEventName' => 'Meeting Rock & Wrestling',
                    'eventName' => 'Meeting Rock & Wrestling',
                    'title' => 'Walid Raad',
                    'event_type_color' => '#A7A6B1',
                    'eventTypeColorBackground' => '#A7A6B133',
                    'eventTypeName' => 'Blocker',
                    'eventTypeAbbreviation' => 'BL',
                    'audience' => false,
                    'isLoud' => false,
                    'projectName' => 'Walid Raad',
                    'projectStateColor' => '#3E0717',
                    'projectLeaders' => [
                        [
                            'profile_photo_url' => '/profile-photos/photo-1499996860823-5214fcc65f8f.jpg',
                            'first_name' => 'Max',
                            'last_name' => 'Müller'
                        ],
                        [
                            'profile_photo_url' => '/profile-photos/photo-1499996860823-5214fcc65f8f.jpg',
                            'first_name' => 'Max',
                            'last_name' => 'Müller'
                        ],
                        [
                            'profile_photo_url' => '/profile-photos/photo-1499996860823-5214fcc65f8f.jpg',
                            'first_name' => 'Max',
                            'last_name' => 'Müller'
                        ]
                    ],
                    'subEvents' => [],
                    'shifts' => [
                        [
                            'id' => 1,
                            'craft' => [
                                'id' => 1,
                                'name' => 'Caldero-Systems GmbH',
                                'abbreviation' => 'CS'
                            ],
                            'worker_count' => 30,
                            'max_worker_count' => 35
                        ],
                        [
                            'id' => 1,
                            'craft' => [
                                'id' => 1,
                                'name' => 'Caldero-Systems GmbH',
                                'abbreviation' => 'CS'
                            ],
                            'worker_count' => 30,
                            'max_worker_count' => 35
                        ],
                        [
                            'id' => 1,
                            'craft' => [
                                'id' => 1,
                                'name' => 'Caldero-Systems GmbH',
                                'abbreviation' => 'CS'
                            ],
                            'worker_count' => 30,
                            'max_worker_count' => 35
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider toArrayTestDataProvider
     * @throws Throwable
     */
    //phpcs:ignore: Generic.Metrics.CyclomaticComplexity.MaxExceeded
    public function testToArrayWithProject(
        int $expectedEventGetAttributeInvocations,
        array $expectedEventGetAttributeKeys,
        array $expectedEventGetAttributeReturnValues,
        int $expectedProjectGetAttributeInvocations,
        array $expectedProjectGetAttributeKeys,
        array $expectedProjectGetAttributeReturnValues,
        string $expectedProjectGetRelationKey,
        array $expectedManagerUsersCollectionMockAllReturnValue,
        string $expectedProjectStateGetAttributeKey,
        string $expectedProjectStateGetAttributeReturnValue,
        int $expectedManagerUserMockGetAttributeInvocations,
        array $expectedManagerUserMockGetAttributeKeys,
        array $expectedManagerUserMockGetAttributeReturnValues,
        int $expectedCarbonMockUtcInvocationCount,
        int $expectedCarbonMockToIso8601StringInvocationCount,
        array $expectedCarbonMockToIso8601StringReturnValues,
        int $expectedEventTypeMockGetAttributeInvocations,
        array $expectedEventTypeMockGetAttributeKeys,
        array $expectedEventTypeMockGetAttributeReturnValues,
        array $expectedShiftsCollectionMockAllReturnValue,
        int $expectedShiftMockGetAttributeInvocations,
        array $expectedShiftMockGetAttributeKeys,
        array $expectedShiftMockGetAttributeReturnValues,
        int $expectedCraftMockGetAttributeInvocations,
        array $expectedCraftMockGetAttributeKeys,
        array $expectedCraftMockGetAttributeReturnValues,
        int $expectedShiftRelationCollectionMockCountInvocationCount,
        int $expectedShiftRelationCollectionMockCountReturnValue,
        int $expectedShiftsQualificationsCollectionMockInvocationCount,
        string $expectedShiftsQualificationsCollectionMockSumKey,
        int $expectedShiftsQualificationsCollectionMockSumReturnValue,
        int $expectedCreatorMockGetAttributeInvocations,
        array $expectedCreatorMockGetAttributeKeys,
        array $expectedCreatorMockGetAttributeReturnValues,
        array $expectedResult
    ): void {
        $this->projectMock->expects($matcher = $this->exactly($expectedProjectGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedProjectGetAttributeKeys,
                    $expectedProjectGetAttributeReturnValues,
                ): string|Collection {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    switch (true) {
                        case (isset($expectedProjectGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedProjectGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            if (
                                !str_contains(
                                    $desiredReturn = $expectedProjectGetAttributeReturnValues[$desiredIndex],
                                    'Mock'
                                )
                            ) {
                                return $desiredReturn;
                            }

                            return $this->{$desiredReturn};
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->projectMock->expects($this->once())
            ->method('getRelation')
            ->with($expectedProjectGetRelationKey)
            ->willReturn($this->projectStateMock);

        $this->projectStateMock->expects($this->once())
            ->method('getAttribute')
            ->with($expectedProjectStateGetAttributeKey)
            ->willReturn($expectedProjectStateGetAttributeReturnValue);

        $this->managerUsersCollectionMock->expects($this->once())
            ->method('all')
            ->willReturn(
                array_map(
                    function (string $desiredMock): User {
                        return $this->{$desiredMock};
                    },
                    $expectedManagerUsersCollectionMockAllReturnValue
                )
            );

        $this->managerUserMock->expects($matcher = $this->exactly($expectedManagerUserMockGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedManagerUserMockGetAttributeKeys,
                    $expectedManagerUserMockGetAttributeReturnValues,
                ): string {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);

                    $desiredIndex = ($desiredIndex > 2 && $desiredIndex < 6) ?
                        ($desiredIndex - 3) : (
                        ($desiredIndex > 5) ?
                            ($desiredIndex - 6) :
                            $desiredIndex
                        );

                    switch (true) {
                        case (isset($expectedManagerUserMockGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedManagerUserMockGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            return $expectedManagerUserMockGetAttributeReturnValues[$desiredIndex];
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->carbonMock->expects($this->exactly($expectedCarbonMockUtcInvocationCount))
            ->method('utc')
            ->willReturnSelf();

        $this->carbonMock->expects($matcher = $this->exactly($expectedCarbonMockToIso8601StringInvocationCount))
            ->method('toIso8601String')
            ->willReturnCallback(
                function () use (
                    $matcher,
                    $expectedCarbonMockToIso8601StringReturnValues,
                ): string {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    switch (true) {
                        case (isset($expectedCarbonMockToIso8601StringReturnValues[$desiredIndex])):
                            $this->assertCount(0, func_get_args());
                            return $expectedCarbonMockToIso8601StringReturnValues[$desiredIndex];
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->eventTypeMock->expects($matcher = $this->exactly($expectedEventTypeMockGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedEventTypeMockGetAttributeKeys,
                    $expectedEventTypeMockGetAttributeReturnValues,
                ): string {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    switch (true) {
                        case (isset($expectedEventTypeMockGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedEventTypeMockGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            return $expectedEventTypeMockGetAttributeReturnValues[$desiredIndex];
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->shiftsCollectionMock->expects($this->once())
            ->method('all')
            ->willReturn(
                array_map(
                    function (string $desiredMock): Shift {
                        return $this->{$desiredMock};
                    },
                    $expectedShiftsCollectionMockAllReturnValue
                )
            );

        $this->craftMock->expects($matcher = $this->exactly($expectedCraftMockGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedCraftMockGetAttributeKeys,
                    $expectedCraftMockGetAttributeReturnValues,
                ): mixed {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    $desiredIndex = ($desiredIndex > 2 && $desiredIndex < 6) ?
                        ($desiredIndex - 3) : (
                        ($desiredIndex > 5) ?
                            ($desiredIndex - 6) :
                            $desiredIndex
                        );
                    switch (true) {
                        case (isset($expectedCraftMockGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedCraftMockGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            if (
                                !is_string(
                                    $desiredReturn = $expectedCraftMockGetAttributeReturnValues[$desiredIndex]
                                ) ||
                                !str_contains($desiredReturn, 'Mock')
                            ) {
                                return $desiredReturn;
                            }

                            return $this->{$desiredReturn};
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->shiftRelationCollectionMock
            ->expects($this->exactly($expectedShiftRelationCollectionMockCountInvocationCount))
            ->method('count')
            ->willReturn($expectedShiftRelationCollectionMockCountReturnValue);

        $this->shiftsQualificationsCollectionMock
            ->expects($this->exactly($expectedShiftsQualificationsCollectionMockInvocationCount))
            ->method('sum')
            ->with($expectedShiftsQualificationsCollectionMockSumKey)
            ->willReturn($expectedShiftsQualificationsCollectionMockSumReturnValue);

        $this->shiftMock->expects($matcher = $this->exactly($expectedShiftMockGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedShiftMockGetAttributeKeys,
                    $expectedShiftMockGetAttributeReturnValues,
                ): mixed {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    $desiredIndex = ($desiredIndex > 7 && $desiredIndex < 16) ?
                        ($desiredIndex - 8) : (
                        ($desiredIndex > 15) ?
                            ($desiredIndex - 16) :
                            $desiredIndex
                        );
                    switch (true) {
                        case (isset($expectedShiftMockGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedShiftMockGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            if (
                                !is_string(
                                    $desiredReturn = $expectedShiftMockGetAttributeReturnValues[$desiredIndex]
                                ) ||
                                !str_contains($desiredReturn, 'Mock')
                            ) {
                                return $desiredReturn;
                            }

                            return $this->{$desiredReturn};
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->creatorMock->expects($matcher = $this->exactly($expectedCreatorMockGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedCreatorMockGetAttributeKeys,
                    $expectedCreatorMockGetAttributeReturnValues,
                ): mixed {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    switch (true) {
                        case (isset($expectedCreatorMockGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedCreatorMockGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            return $expectedCreatorMockGetAttributeReturnValues[$desiredIndex];
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->eventMock->expects($matcher = $this->exactly($expectedEventGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedEventGetAttributeKeys,
                    $expectedEventGetAttributeReturnValues,
                ): mixed {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    switch (true) {
                        case (isset($expectedEventGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedEventGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            if (
                                !is_string($desiredReturn = $expectedEventGetAttributeReturnValues[$desiredIndex]) ||
                                !str_contains($desiredReturn, 'Mock')
                            ) {
                                return $desiredReturn;
                            }

                            return $this->{$desiredReturn};
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        //set mock for startTime only by given value (carbonMock)
        $expectedResult['startTime'] = $this->{$expectedResult['startTime']};

        $this->assertEquals($expectedResult, $this->getResource()->toArray(Request::create('/')));
    }

    /**
     * @return array<int, mixed>
     */
    public static function toArrayWithoutProjectTestDataProvider(): array
    {
        return [
            'test to array without project' => [
                //expected event getAttribute invocation count
                14,
                //expected event getAttribute keys
                [
                    'project_id',
                    'project',
                    'creator',
                    'event_type',
                    'eventName',
                    'start_time',
                    'id',
                    'room_id',
                    'end_time',
                    'allDay',
                    'audience',
                    'is_loud',
                    'subEvents',
                    'shifts'
                ],
                //expected event getAttribute returns
                [
                    1,
                    null,
                    'creatorMock',
                    'eventTypeMock',
                    'Meeting Rock & Wrestling',
                    'carbonMock',
                    2,
                    3,
                    'carbonMock',
                    true,
                    false,
                    false,
                    [],
                    'shiftsCollectionMock'
                ],
                //expected carbon mock utc invocation count
                2,
                //expected carbon mock toIso8601String invocation count,
                2,
                //expected carbon mock toIso8601String returns
                [
                    '2024-07-01T21:59:59+00:00',
                    '2024-07-02T21:59:59+00:00'
                ],
                4,
                //expected eventTypeMock getAttribute keys
                [
                    'hex_code',
                    'hex_code',
                    'name',
                    'abbreviation'
                ],
                //expected eventTypeMock getAttribute returns
                [
                    '#A7A6B1',
                    '#A7A6B1',
                    'Blocker',
                    'BL'
                ],
                //expected shiftsCollectionMock all return values
                [
                    'shiftMock',
                    'shiftMock',
                    'shiftMock'
                ],
                //expected shiftMock getAttribute invocation count
                24,
                //expected shiftMock getAttribute keys
                [
                    'id',
                    'craft',
                    'craft',
                    'craft',
                    'users',
                    'freelancer',
                    'serviceProvider',
                    'shiftsQualifications'
                ],
                //expected shiftMock getAttribute returns
                [
                    1,
                    'craftMock',
                    'craftMock',
                    'craftMock',
                    'shiftRelationCollectionMock',
                    'shiftRelationCollectionMock',
                    'shiftRelationCollectionMock',
                    'shiftsQualificationsCollectionMock'
                ],
                //expected craftMock getAttribute invocation count
                9,
                //expected craftMock getAttribute keys
                [
                    'id',
                    'name',
                    'abbreviation'
                ],
                //expected craftMock getAttribute returns
                [
                    1,
                    'Caldero-Systems GmbH',
                    'CS'
                ],
                //expected shiftRelationCollection count invocation count
                9,
                //expected shiftRelationCollectionMock count return value
                10,
                //expected shiftsQualificationsCollectionMock sum invocation count
                3,
                //expected shiftsQualificationsCollectionMock sum key
                'value',
                //expected shiftsQualificationsCollectionMock sum return
                35,
                //expected creatorMock getAttribute invocation count
                4,
                //expected creatorMock getAttribute keys
                [
                    'id',
                    'profile_photo_url',
                    'first_name',
                    'last_name'
                ],
                //expected creatorMock getAttribute returns
                [
                    1,
                    '/profile-photos/photo-1499996860823-5214fcc65f8f.jpg',
                    'Max',
                    'Müller'
                ],
                //expected result
                [
                    'id' => 2,
                    'projectId' => 1,
                    'roomId' => 3,
                    'created_by' => [
                        'id' => 1,
                        'profile_photo_url' => '/profile-photos/photo-1499996860823-5214fcc65f8f.jpg',
                        'first_name' => 'Max',
                        'last_name' => 'Müller'
                    ],
                    'start' => "2024-07-01T21:59:59+00:00",
                    'startTime' => 'carbonMock',
                    'end' => '2024-07-02T21:59:59+00:00',
                    'allDay' => true,
                    'alwaysEventName' => 'Meeting Rock & Wrestling',
                    'eventName' => 'Meeting Rock & Wrestling',
                    'title' => 'Meeting Rock & Wrestling',
                    'event_type_color' => '#A7A6B1',
                    'eventTypeColorBackground' => '#A7A6B133',
                    'eventTypeName' => 'Blocker',
                    'eventTypeAbbreviation' => 'BL',
                    'audience' => false,
                    'isLoud' => false,
                    'projectName' => null,
                    'projectStateColor' => null,
                    'projectLeaders' => null,
                    'subEvents' => [],
                    'shifts' => [
                        [
                            'id' => 1,
                            'craft' => [
                                'id' => 1,
                                'name' => 'Caldero-Systems GmbH',
                                'abbreviation' => 'CS'
                            ],
                            'worker_count' => 30,
                            'max_worker_count' => 35
                        ],
                        [
                            'id' => 1,
                            'craft' => [
                                'id' => 1,
                                'name' => 'Caldero-Systems GmbH',
                                'abbreviation' => 'CS'
                            ],
                            'worker_count' => 30,
                            'max_worker_count' => 35
                        ],
                        [
                            'id' => 1,
                            'craft' => [
                                'id' => 1,
                                'name' => 'Caldero-Systems GmbH',
                                'abbreviation' => 'CS'
                            ],
                            'worker_count' => 30,
                            'max_worker_count' => 35
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider toArrayWithoutProjectTestDataProvider
     * @throws Throwable
     */
    //phpcs:ignore: Generic.Metrics.CyclomaticComplexity.TooHigh
    public function testToArrayWithoutProject(
        int $expectedEventGetAttributeInvocations,
        array $expectedEventGetAttributeKeys,
        array $expectedEventGetAttributeReturnValues,
        int $expectedCarbonMockUtcInvocationCount,
        int $expectedCarbonMockToIso8601StringInvocationCount,
        array $expectedCarbonMockToIso8601StringReturnValues,
        int $expectedEventTypeMockGetAttributeInvocations,
        array $expectedEventTypeMockGetAttributeKeys,
        array $expectedEventTypeMockGetAttributeReturnValues,
        array $expectedShiftsCollectionMockAllReturnValue,
        int $expectedShiftMockGetAttributeInvocations,
        array $expectedShiftMockGetAttributeKeys,
        array $expectedShiftMockGetAttributeReturnValues,
        int $expectedCraftMockGetAttributeInvocations,
        array $expectedCraftMockGetAttributeKeys,
        array $expectedCraftMockGetAttributeReturnValues,
        int $expectedShiftRelationCollectionMockCountInvocationCount,
        int $expectedShiftRelationCollectionMockCountReturnValue,
        int $expectedShiftsQualificationsCollectionMockInvocationCount,
        string $expectedShiftsQualificationsCollectionMockSumKey,
        int $expectedShiftsQualificationsCollectionMockSumReturnValue,
        int $expectedCreatorMockGetAttributeInvocations,
        array $expectedCreatorMockGetAttributeKeys,
        array $expectedCreatorMockGetAttributeReturnValues,
        array $expectedResult
    ): void {
        $this->projectMock->expects($this->never())
            ->method('getRelation');

        $this->projectStateMock->expects($this->never())
            ->method('getAttribute');


        $this->carbonMock->expects($this->exactly($expectedCarbonMockUtcInvocationCount))
            ->method('utc')
            ->willReturnSelf();

        $this->carbonMock->expects($matcher = $this->exactly($expectedCarbonMockToIso8601StringInvocationCount))
            ->method('toIso8601String')
            ->willReturnCallback(
                function () use (
                    $matcher,
                    $expectedCarbonMockToIso8601StringReturnValues,
                ): string {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    switch (true) {
                        case (isset($expectedCarbonMockToIso8601StringReturnValues[$desiredIndex])):
                            $this->assertCount(0, func_get_args());
                            return $expectedCarbonMockToIso8601StringReturnValues[$desiredIndex];
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->eventTypeMock->expects($matcher = $this->exactly($expectedEventTypeMockGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedEventTypeMockGetAttributeKeys,
                    $expectedEventTypeMockGetAttributeReturnValues,
                ): string {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    switch (true) {
                        case (isset($expectedEventTypeMockGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedEventTypeMockGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            return $expectedEventTypeMockGetAttributeReturnValues[$desiredIndex];
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->shiftsCollectionMock->expects($this->once())
            ->method('all')
            ->willReturn(
                array_map(
                    function (string $desiredMock): Shift {
                        return $this->{$desiredMock};
                    },
                    $expectedShiftsCollectionMockAllReturnValue
                )
            );

        $this->craftMock->expects($matcher = $this->exactly($expectedCraftMockGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedCraftMockGetAttributeKeys,
                    $expectedCraftMockGetAttributeReturnValues,
                ): mixed {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    $desiredIndex = ($desiredIndex > 2 && $desiredIndex < 6) ?
                        ($desiredIndex - 3) : (
                        ($desiredIndex > 5) ?
                            ($desiredIndex - 6) :
                            $desiredIndex
                        );
                    switch (true) {
                        case (isset($expectedCraftMockGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedCraftMockGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            if (
                                !is_string(
                                    $desiredReturn = $expectedCraftMockGetAttributeReturnValues[$desiredIndex]
                                ) ||
                                !str_contains($desiredReturn, 'Mock')
                            ) {
                                return $desiredReturn;
                            }

                            return $this->{$desiredReturn};
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->shiftRelationCollectionMock
            ->expects($this->exactly($expectedShiftRelationCollectionMockCountInvocationCount))
            ->method('count')
            ->willReturn($expectedShiftRelationCollectionMockCountReturnValue);

        $this->shiftsQualificationsCollectionMock
            ->expects($this->exactly($expectedShiftsQualificationsCollectionMockInvocationCount))
            ->method('sum')
            ->with($expectedShiftsQualificationsCollectionMockSumKey)
            ->willReturn($expectedShiftsQualificationsCollectionMockSumReturnValue);

        $this->shiftMock->expects($matcher = $this->exactly($expectedShiftMockGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedShiftMockGetAttributeKeys,
                    $expectedShiftMockGetAttributeReturnValues,
                ): mixed {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    $desiredIndex = ($desiredIndex > 7 && $desiredIndex < 16) ?
                        ($desiredIndex - 8) : (
                        ($desiredIndex > 15) ?
                            ($desiredIndex - 16) :
                            $desiredIndex
                        );
                    switch (true) {
                        case (isset($expectedShiftMockGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedShiftMockGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            if (
                                !is_string(
                                    $desiredReturn = $expectedShiftMockGetAttributeReturnValues[$desiredIndex]
                                ) ||
                                !str_contains($desiredReturn, 'Mock')
                            ) {
                                return $desiredReturn;
                            }

                            return $this->{$desiredReturn};
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->creatorMock->expects($matcher = $this->exactly($expectedCreatorMockGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedCreatorMockGetAttributeKeys,
                    $expectedCreatorMockGetAttributeReturnValues,
                ): mixed {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    switch (true) {
                        case (isset($expectedCreatorMockGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedCreatorMockGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            return $expectedCreatorMockGetAttributeReturnValues[$desiredIndex];
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->eventMock->expects($matcher = $this->exactly($expectedEventGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedEventGetAttributeKeys,
                    $expectedEventGetAttributeReturnValues,
                ): mixed {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    switch (true) {
                        case (isset($expectedEventGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedEventGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            if (
                                !is_string($desiredReturn = $expectedEventGetAttributeReturnValues[$desiredIndex]) ||
                                !str_contains($desiredReturn, 'Mock')
                            ) {
                                return $desiredReturn;
                            }

                            return $this->{$desiredReturn};
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        //set mock for startTime only by given value (carbonMock)
        $expectedResult['startTime'] = $this->{$expectedResult['startTime']};

        $this->assertEquals($expectedResult, $this->getResource()->toArray(Request::create('/')));
    }

    //testToArrayWithoutProjectState

    /**
     * @return array<int, mixed>
     */
    public static function toArrayWithoutProjectStateTestDataProvider(): array
    {
        return [
            'test to array without project state' => [
                //expected event getAttribute invocation count
                14,
                //expected event getAttribute keys
                [
                    'project_id',
                    'project',
                    'creator',
                    'event_type',
                    'eventName',
                    'start_time',
                    'id',
                    'room_id',
                    'end_time',
                    'allDay',
                    'audience',
                    'is_loud',
                    'subEvents',
                    'shifts'
                ],
                //expected event getAttribute returns
                [
                    1,
                    'projectMock',
                    'creatorMock',
                    'eventTypeMock',
                    'Meeting Rock & Wrestling',
                    'carbonMock',
                    2,
                    3,
                    'carbonMock',
                    true,
                    false,
                    false,
                    [],
                    'shiftsCollectionMock'
                ],
                //expected project getAttribute invocation count
                2,
                //expected project getAttribute keys
                [
                    'name',
                    'managerUsers'
                ],
                //expected project getAttribute returns
                [
                    'Walid Raad',
                    'managerUsersCollectionMock'
                ],
                'state',
                //expected managerUsersCollectionMock all return values
                [
                    'managerUserMock',
                    'managerUserMock',
                    'managerUserMock'
                ],
                //expected managerUserMock getAttribute invocation count
                9,
                //expected managerUserMock getAttribute keys
                [
                    'profile_photo_url',
                    'first_name',
                    'last_name',
                ],
                //expected managerUserMock getAttribute returns
                [
                    '/profile-photos/photo-1499996860823-5214fcc65f8f.jpg',
                    'Max',
                    'Müller'
                ],
                //expected carbon mock utc invocation count
                2,
                //expected carbon mock toIso8601String invocation count,
                2,
                //expected carbon mock toIso8601String returns
                [
                    '2024-07-01T21:59:59+00:00',
                    '2024-07-02T21:59:59+00:00'
                ],
                4,
                //expected eventTypeMock getAttribute keys
                [
                    'hex_code',
                    'hex_code',
                    'name',
                    'abbreviation'
                ],
                //expected eventTypeMock getAttribute returns
                [
                    '#A7A6B1',
                    '#A7A6B1',
                    'Blocker',
                    'BL'
                ],
                //expected shiftsCollectionMock all return values
                [
                    'shiftMock',
                    'shiftMock',
                    'shiftMock'
                ],
                //expected shiftMock getAttribute invocation count
                24,
                //expected shiftMock getAttribute keys
                [
                    'id',
                    'craft',
                    'craft',
                    'craft',
                    'users',
                    'freelancer',
                    'serviceProvider',
                    'shiftsQualifications'
                ],
                //expected shiftMock getAttribute returns
                [
                    1,
                    'craftMock',
                    'craftMock',
                    'craftMock',
                    'shiftRelationCollectionMock',
                    'shiftRelationCollectionMock',
                    'shiftRelationCollectionMock',
                    'shiftsQualificationsCollectionMock'
                ],
                //expected craftMock getAttribute invocation count
                9,
                //expected craftMock getAttribute keys
                [
                    'id',
                    'name',
                    'abbreviation'
                ],
                //expected craftMock getAttribute returns
                [
                    1,
                    'Caldero-Systems GmbH',
                    'CS'
                ],
                //expected shiftRelationCollection count invocation count
                9,
                //expected shiftRelationCollectionMock count return value
                10,
                //expected shiftsQualificationsCollectionMock sum invocation count
                3,
                //expected shiftsQualificationsCollectionMock sum key
                'value',
                //expected shiftsQualificationsCollectionMock sum return
                35,
                //expected creatorMock getAttribute invocation count
                4,
                //expected creatorMock getAttribute keys
                [
                    'id',
                    'profile_photo_url',
                    'first_name',
                    'last_name'
                ],
                //expected creatorMock getAttribute returns
                [
                    1,
                    '/profile-photos/photo-1499996860823-5214fcc65f8f.jpg',
                    'Max',
                    'Müller'
                ],
                //expected result
                [
                    'id' => 2,
                    'projectId' => 1,
                    'roomId' => 3,
                    'created_by' => [
                        'id' => 1,
                        'profile_photo_url' => '/profile-photos/photo-1499996860823-5214fcc65f8f.jpg',
                        'first_name' => 'Max',
                        'last_name' => 'Müller'
                    ],
                    'start' => "2024-07-01T21:59:59+00:00",
                    'startTime' => 'carbonMock',
                    'end' => '2024-07-02T21:59:59+00:00',
                    'allDay' => true,
                    'alwaysEventName' => 'Meeting Rock & Wrestling',
                    'eventName' => 'Meeting Rock & Wrestling',
                    'title' => 'Walid Raad',
                    'event_type_color' => '#A7A6B1',
                    'eventTypeColorBackground' => '#A7A6B133',
                    'eventTypeName' => 'Blocker',
                    'eventTypeAbbreviation' => 'BL',
                    'audience' => false,
                    'isLoud' => false,
                    'projectName' => 'Walid Raad',
                    'projectStateColor' => null,
                    'projectLeaders' => [
                        [
                            'profile_photo_url' => '/profile-photos/photo-1499996860823-5214fcc65f8f.jpg',
                            'first_name' => 'Max',
                            'last_name' => 'Müller'
                        ],
                        [
                            'profile_photo_url' => '/profile-photos/photo-1499996860823-5214fcc65f8f.jpg',
                            'first_name' => 'Max',
                            'last_name' => 'Müller'
                        ],
                        [
                            'profile_photo_url' => '/profile-photos/photo-1499996860823-5214fcc65f8f.jpg',
                            'first_name' => 'Max',
                            'last_name' => 'Müller'
                        ]
                    ],
                    'subEvents' => [],
                    'shifts' => [
                        [
                            'id' => 1,
                            'craft' => [
                                'id' => 1,
                                'name' => 'Caldero-Systems GmbH',
                                'abbreviation' => 'CS'
                            ],
                            'worker_count' => 30,
                            'max_worker_count' => 35
                        ],
                        [
                            'id' => 1,
                            'craft' => [
                                'id' => 1,
                                'name' => 'Caldero-Systems GmbH',
                                'abbreviation' => 'CS'
                            ],
                            'worker_count' => 30,
                            'max_worker_count' => 35
                        ],
                        [
                            'id' => 1,
                            'craft' => [
                                'id' => 1,
                                'name' => 'Caldero-Systems GmbH',
                                'abbreviation' => 'CS'
                            ],
                            'worker_count' => 30,
                            'max_worker_count' => 35
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider toArrayWithoutProjectStateTestDataProvider
     * @throws Throwable
     */
    //phpcs:ignore: Generic.Metrics.CyclomaticComplexity.MaxExceeded
    public function testToArrayWithoutProjectState(
        int $expectedEventGetAttributeInvocations,
        array $expectedEventGetAttributeKeys,
        array $expectedEventGetAttributeReturnValues,
        int $expectedProjectGetAttributeInvocations,
        array $expectedProjectGetAttributeKeys,
        array $expectedProjectGetAttributeReturnValues,
        string $expectedProjectGetRelationKey,
        array $expectedManagerUsersCollectionMockAllReturnValue,
        int $expectedManagerUserMockGetAttributeInvocations,
        array $expectedManagerUserMockGetAttributeKeys,
        array $expectedManagerUserMockGetAttributeReturnValues,
        int $expectedCarbonMockUtcInvocationCount,
        int $expectedCarbonMockToIso8601StringInvocationCount,
        array $expectedCarbonMockToIso8601StringReturnValues,
        int $expectedEventTypeMockGetAttributeInvocations,
        array $expectedEventTypeMockGetAttributeKeys,
        array $expectedEventTypeMockGetAttributeReturnValues,
        array $expectedShiftsCollectionMockAllReturnValue,
        int $expectedShiftMockGetAttributeInvocations,
        array $expectedShiftMockGetAttributeKeys,
        array $expectedShiftMockGetAttributeReturnValues,
        int $expectedCraftMockGetAttributeInvocations,
        array $expectedCraftMockGetAttributeKeys,
        array $expectedCraftMockGetAttributeReturnValues,
        int $expectedShiftRelationCollectionMockCountInvocationCount,
        int $expectedShiftRelationCollectionMockCountReturnValue,
        int $expectedShiftsQualificationsCollectionMockInvocationCount,
        string $expectedShiftsQualificationsCollectionMockSumKey,
        int $expectedShiftsQualificationsCollectionMockSumReturnValue,
        int $expectedCreatorMockGetAttributeInvocations,
        array $expectedCreatorMockGetAttributeKeys,
        array $expectedCreatorMockGetAttributeReturnValues,
        array $expectedResult
    ): void {
        $this->projectMock->expects($matcher = $this->exactly($expectedProjectGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedProjectGetAttributeKeys,
                    $expectedProjectGetAttributeReturnValues,
                ): string|Collection {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    switch (true) {
                        case (isset($expectedProjectGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedProjectGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            if (
                                !str_contains(
                                    $desiredReturn = $expectedProjectGetAttributeReturnValues[$desiredIndex],
                                    'Mock'
                                )
                            ) {
                                return $desiredReturn;
                            }

                            return $this->{$desiredReturn};
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->projectMock->expects($this->once())
            ->method('getRelation')
            ->with($expectedProjectGetRelationKey)
            ->willReturn(null);

        $this->projectStateMock->expects($this->never())
            ->method('getAttribute');

        $this->managerUsersCollectionMock->expects($this->once())
            ->method('all')
            ->willReturn(
                array_map(
                    function (string $desiredMock): User {
                        return $this->{$desiredMock};
                    },
                    $expectedManagerUsersCollectionMockAllReturnValue
                )
            );

        $this->managerUserMock->expects($matcher = $this->exactly($expectedManagerUserMockGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedManagerUserMockGetAttributeKeys,
                    $expectedManagerUserMockGetAttributeReturnValues,
                ): string {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);

                    $desiredIndex = ($desiredIndex > 2 && $desiredIndex < 6) ?
                        ($desiredIndex - 3) : (
                        ($desiredIndex > 5) ?
                            ($desiredIndex - 6) :
                            $desiredIndex
                        );

                    switch (true) {
                        case (isset($expectedManagerUserMockGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedManagerUserMockGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            return $expectedManagerUserMockGetAttributeReturnValues[$desiredIndex];
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->carbonMock->expects($this->exactly($expectedCarbonMockUtcInvocationCount))
            ->method('utc')
            ->willReturnSelf();

        $this->carbonMock->expects($matcher = $this->exactly($expectedCarbonMockToIso8601StringInvocationCount))
            ->method('toIso8601String')
            ->willReturnCallback(
                function () use (
                    $matcher,
                    $expectedCarbonMockToIso8601StringReturnValues,
                ): string {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    switch (true) {
                        case (isset($expectedCarbonMockToIso8601StringReturnValues[$desiredIndex])):
                            $this->assertCount(0, func_get_args());
                            return $expectedCarbonMockToIso8601StringReturnValues[$desiredIndex];
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->eventTypeMock->expects($matcher = $this->exactly($expectedEventTypeMockGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedEventTypeMockGetAttributeKeys,
                    $expectedEventTypeMockGetAttributeReturnValues,
                ): string {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    switch (true) {
                        case (isset($expectedEventTypeMockGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedEventTypeMockGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            return $expectedEventTypeMockGetAttributeReturnValues[$desiredIndex];
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->shiftsCollectionMock->expects($this->once())
            ->method('all')
            ->willReturn(
                array_map(
                    function (string $desiredMock): Shift {
                        return $this->{$desiredMock};
                    },
                    $expectedShiftsCollectionMockAllReturnValue
                )
            );

        $this->craftMock->expects($matcher = $this->exactly($expectedCraftMockGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedCraftMockGetAttributeKeys,
                    $expectedCraftMockGetAttributeReturnValues,
                ): mixed {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    $desiredIndex = ($desiredIndex > 2 && $desiredIndex < 6) ?
                        ($desiredIndex - 3) : (
                        ($desiredIndex > 5) ?
                            ($desiredIndex - 6) :
                            $desiredIndex
                        );
                    switch (true) {
                        case (isset($expectedCraftMockGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedCraftMockGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            if (
                                !is_string(
                                    $desiredReturn = $expectedCraftMockGetAttributeReturnValues[$desiredIndex]
                                ) ||
                                !str_contains($desiredReturn, 'Mock')
                            ) {
                                return $desiredReturn;
                            }

                            return $this->{$desiredReturn};
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->shiftRelationCollectionMock
            ->expects($this->exactly($expectedShiftRelationCollectionMockCountInvocationCount))
            ->method('count')
            ->willReturn($expectedShiftRelationCollectionMockCountReturnValue);

        $this->shiftsQualificationsCollectionMock
            ->expects($this->exactly($expectedShiftsQualificationsCollectionMockInvocationCount))
            ->method('sum')
            ->with($expectedShiftsQualificationsCollectionMockSumKey)
            ->willReturn($expectedShiftsQualificationsCollectionMockSumReturnValue);

        $this->shiftMock->expects($matcher = $this->exactly($expectedShiftMockGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedShiftMockGetAttributeKeys,
                    $expectedShiftMockGetAttributeReturnValues,
                ): mixed {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    $desiredIndex = ($desiredIndex > 7 && $desiredIndex < 16) ?
                        ($desiredIndex - 8) : (
                        ($desiredIndex > 15) ?
                            ($desiredIndex - 16) :
                            $desiredIndex
                        );
                    switch (true) {
                        case (isset($expectedShiftMockGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedShiftMockGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            if (
                                !is_string(
                                    $desiredReturn = $expectedShiftMockGetAttributeReturnValues[$desiredIndex]
                                ) ||
                                !str_contains($desiredReturn, 'Mock')
                            ) {
                                return $desiredReturn;
                            }

                            return $this->{$desiredReturn};
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->creatorMock->expects($matcher = $this->exactly($expectedCreatorMockGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedCreatorMockGetAttributeKeys,
                    $expectedCreatorMockGetAttributeReturnValues,
                ): mixed {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    switch (true) {
                        case (isset($expectedCreatorMockGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedCreatorMockGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            return $expectedCreatorMockGetAttributeReturnValues[$desiredIndex];
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->eventMock->expects($matcher = $this->exactly($expectedEventGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedEventGetAttributeKeys,
                    $expectedEventGetAttributeReturnValues,
                ): mixed {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    switch (true) {
                        case (isset($expectedEventGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedEventGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            if (
                                !is_string($desiredReturn = $expectedEventGetAttributeReturnValues[$desiredIndex]) ||
                                !str_contains($desiredReturn, 'Mock')
                            ) {
                                return $desiredReturn;
                            }

                            return $this->{$desiredReturn};
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        //set mock for startTime only by given value (carbonMock)
        $expectedResult['startTime'] = $this->{$expectedResult['startTime']};

        $this->assertEquals($expectedResult, $this->getResource()->toArray(Request::create('/')));
    }

    //testToArrayGetTitleFromEventType -> get name from event type

    /**
     * @return array<int, mixed>
     */
    public static function toArrayGetTitleFromEventTypeTestDataProvider(): array
    {
        return [
            'test to array get title from event type' => [
                //expected event getAttribute invocation count
                14,
                //expected event getAttribute keys
                [
                    'project_id',
                    'project',
                    'creator',
                    'event_type',
                    'eventName',
                    'start_time',
                    'id',
                    'room_id',
                    'end_time',
                    'allDay',
                    'audience',
                    'is_loud',
                    'subEvents',
                    'shifts'
                ],
                //expected event getAttribute returns
                [
                    1,
                    null,
                    'creatorMock',
                    'eventTypeMock',
                    null,
                    'carbonMock',
                    2,
                    3,
                    'carbonMock',
                    true,
                    false,
                    false,
                    [],
                    'shiftsCollectionMock'
                ],
                //expected carbon mock utc invocation count
                2,
                //expected carbon mock toIso8601String invocation count,
                2,
                //expected carbon mock toIso8601String returns
                [
                    '2024-07-01T21:59:59+00:00',
                    '2024-07-02T21:59:59+00:00'
                ],
                5,
                //expected eventTypeMock getAttribute keys
                [
                    'name',
                    'hex_code',
                    'hex_code',
                    'name',
                    'abbreviation'
                ],
                //expected eventTypeMock getAttribute returns
                [
                    'Blocker',
                    '#A7A6B1',
                    '#A7A6B1',
                    'Blocker',
                    'BL'
                ],
                //expected shiftsCollectionMock all return values
                [
                    'shiftMock',
                    'shiftMock',
                    'shiftMock'
                ],
                //expected shiftMock getAttribute invocation count
                24,
                //expected shiftMock getAttribute keys
                [
                    'id',
                    'craft',
                    'craft',
                    'craft',
                    'users',
                    'freelancer',
                    'serviceProvider',
                    'shiftsQualifications'
                ],
                //expected shiftMock getAttribute returns
                [
                    1,
                    'craftMock',
                    'craftMock',
                    'craftMock',
                    'shiftRelationCollectionMock',
                    'shiftRelationCollectionMock',
                    'shiftRelationCollectionMock',
                    'shiftsQualificationsCollectionMock'
                ],
                //expected craftMock getAttribute invocation count
                9,
                //expected craftMock getAttribute keys
                [
                    'id',
                    'name',
                    'abbreviation'
                ],
                //expected craftMock getAttribute returns
                [
                    1,
                    'Caldero-Systems GmbH',
                    'CS'
                ],
                //expected shiftRelationCollection count invocation count
                9,
                //expected shiftRelationCollectionMock count return value
                10,
                //expected shiftsQualificationsCollectionMock sum invocation count
                3,
                //expected shiftsQualificationsCollectionMock sum key
                'value',
                //expected shiftsQualificationsCollectionMock sum return
                35,
                //expected creatorMock getAttribute invocation count
                4,
                //expected creatorMock getAttribute keys
                [
                    'id',
                    'profile_photo_url',
                    'first_name',
                    'last_name'
                ],
                //expected creatorMock getAttribute returns
                [
                    1,
                    '/profile-photos/photo-1499996860823-5214fcc65f8f.jpg',
                    'Max',
                    'Müller'
                ],
                //expected result
                [
                    'id' => 2,
                    'projectId' => 1,
                    'roomId' => 3,
                    'created_by' => [
                        'id' => 1,
                        'profile_photo_url' => '/profile-photos/photo-1499996860823-5214fcc65f8f.jpg',
                        'first_name' => 'Max',
                        'last_name' => 'Müller'
                    ],
                    'start' => "2024-07-01T21:59:59+00:00",
                    'startTime' => 'carbonMock',
                    'end' => '2024-07-02T21:59:59+00:00',
                    'allDay' => true,
                    'alwaysEventName' => null,
                    'eventName' => null,
                    'title' => 'Blocker',
                    'event_type_color' => '#A7A6B1',
                    'eventTypeColorBackground' => '#A7A6B133',
                    'eventTypeName' => 'Blocker',
                    'eventTypeAbbreviation' => 'BL',
                    'audience' => false,
                    'isLoud' => false,
                    'projectName' => null,
                    'projectStateColor' => null,
                    'projectLeaders' => null,
                    'subEvents' => [],
                    'shifts' => [
                        [
                            'id' => 1,
                            'craft' => [
                                'id' => 1,
                                'name' => 'Caldero-Systems GmbH',
                                'abbreviation' => 'CS'
                            ],
                            'worker_count' => 30,
                            'max_worker_count' => 35
                        ],
                        [
                            'id' => 1,
                            'craft' => [
                                'id' => 1,
                                'name' => 'Caldero-Systems GmbH',
                                'abbreviation' => 'CS'
                            ],
                            'worker_count' => 30,
                            'max_worker_count' => 35
                        ],
                        [
                            'id' => 1,
                            'craft' => [
                                'id' => 1,
                                'name' => 'Caldero-Systems GmbH',
                                'abbreviation' => 'CS'
                            ],
                            'worker_count' => 30,
                            'max_worker_count' => 35
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider toArrayGetTitleFromEventTypeTestDataProvider
     * @throws Throwable
     */
    //phpcs:ignore: Generic.Metrics.CyclomaticComplexity.TooHigh
    public function testToArrayGetTitleFromEventType(
        int $expectedEventGetAttributeInvocations,
        array $expectedEventGetAttributeKeys,
        array $expectedEventGetAttributeReturnValues,
        int $expectedCarbonMockUtcInvocationCount,
        int $expectedCarbonMockToIso8601StringInvocationCount,
        array $expectedCarbonMockToIso8601StringReturnValues,
        int $expectedEventTypeMockGetAttributeInvocations,
        array $expectedEventTypeMockGetAttributeKeys,
        array $expectedEventTypeMockGetAttributeReturnValues,
        array $expectedShiftsCollectionMockAllReturnValue,
        int $expectedShiftMockGetAttributeInvocations,
        array $expectedShiftMockGetAttributeKeys,
        array $expectedShiftMockGetAttributeReturnValues,
        int $expectedCraftMockGetAttributeInvocations,
        array $expectedCraftMockGetAttributeKeys,
        array $expectedCraftMockGetAttributeReturnValues,
        int $expectedShiftRelationCollectionMockCountInvocationCount,
        int $expectedShiftRelationCollectionMockCountReturnValue,
        int $expectedShiftsQualificationsCollectionMockInvocationCount,
        string $expectedShiftsQualificationsCollectionMockSumKey,
        int $expectedShiftsQualificationsCollectionMockSumReturnValue,
        int $expectedCreatorMockGetAttributeInvocations,
        array $expectedCreatorMockGetAttributeKeys,
        array $expectedCreatorMockGetAttributeReturnValues,
        array $expectedResult
    ): void {
        $this->projectMock->expects($this->never())
            ->method('getRelation');

        $this->projectStateMock->expects($this->never())
            ->method('getAttribute');


        $this->carbonMock->expects($this->exactly($expectedCarbonMockUtcInvocationCount))
            ->method('utc')
            ->willReturnSelf();

        $this->carbonMock->expects($matcher = $this->exactly($expectedCarbonMockToIso8601StringInvocationCount))
            ->method('toIso8601String')
            ->willReturnCallback(
                function () use (
                    $matcher,
                    $expectedCarbonMockToIso8601StringReturnValues,
                ): string {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    switch (true) {
                        case (isset($expectedCarbonMockToIso8601StringReturnValues[$desiredIndex])):
                            $this->assertCount(0, func_get_args());
                            return $expectedCarbonMockToIso8601StringReturnValues[$desiredIndex];
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->eventTypeMock->expects($matcher = $this->exactly($expectedEventTypeMockGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedEventTypeMockGetAttributeKeys,
                    $expectedEventTypeMockGetAttributeReturnValues,
                ): string {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    switch (true) {
                        case (isset($expectedEventTypeMockGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedEventTypeMockGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            return $expectedEventTypeMockGetAttributeReturnValues[$desiredIndex];
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->shiftsCollectionMock->expects($this->once())
            ->method('all')
            ->willReturn(
                array_map(
                    function (string $desiredMock): Shift {
                        return $this->{$desiredMock};
                    },
                    $expectedShiftsCollectionMockAllReturnValue
                )
            );

        $this->craftMock->expects($matcher = $this->exactly($expectedCraftMockGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedCraftMockGetAttributeKeys,
                    $expectedCraftMockGetAttributeReturnValues,
                ): mixed {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    $desiredIndex = ($desiredIndex > 2 && $desiredIndex < 6) ?
                        ($desiredIndex - 3) : (
                        ($desiredIndex > 5) ?
                            ($desiredIndex - 6) :
                            $desiredIndex
                        );
                    switch (true) {
                        case (isset($expectedCraftMockGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedCraftMockGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            if (
                                !is_string(
                                    $desiredReturn = $expectedCraftMockGetAttributeReturnValues[$desiredIndex]
                                ) ||
                                !str_contains($desiredReturn, 'Mock')
                            ) {
                                return $desiredReturn;
                            }

                            return $this->{$desiredReturn};
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->shiftRelationCollectionMock
            ->expects($this->exactly($expectedShiftRelationCollectionMockCountInvocationCount))
            ->method('count')
            ->willReturn($expectedShiftRelationCollectionMockCountReturnValue);

        $this->shiftsQualificationsCollectionMock
            ->expects($this->exactly($expectedShiftsQualificationsCollectionMockInvocationCount))
            ->method('sum')
            ->with($expectedShiftsQualificationsCollectionMockSumKey)
            ->willReturn($expectedShiftsQualificationsCollectionMockSumReturnValue);

        $this->shiftMock->expects($matcher = $this->exactly($expectedShiftMockGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedShiftMockGetAttributeKeys,
                    $expectedShiftMockGetAttributeReturnValues,
                ): mixed {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    $desiredIndex = ($desiredIndex > 7 && $desiredIndex < 16) ?
                        ($desiredIndex - 8) : (
                        ($desiredIndex > 15) ?
                            ($desiredIndex - 16) :
                            $desiredIndex
                        );
                    switch (true) {
                        case (isset($expectedShiftMockGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedShiftMockGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            if (
                                !is_string(
                                    $desiredReturn = $expectedShiftMockGetAttributeReturnValues[$desiredIndex]
                                ) ||
                                !str_contains($desiredReturn, 'Mock')
                            ) {
                                return $desiredReturn;
                            }

                            return $this->{$desiredReturn};
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->creatorMock->expects($matcher = $this->exactly($expectedCreatorMockGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedCreatorMockGetAttributeKeys,
                    $expectedCreatorMockGetAttributeReturnValues,
                ): mixed {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    switch (true) {
                        case (isset($expectedCreatorMockGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedCreatorMockGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            return $expectedCreatorMockGetAttributeReturnValues[$desiredIndex];
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->eventMock->expects($matcher = $this->exactly($expectedEventGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedEventGetAttributeKeys,
                    $expectedEventGetAttributeReturnValues,
                ): mixed {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    switch (true) {
                        case (isset($expectedEventGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedEventGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            if (
                                !is_string($desiredReturn = $expectedEventGetAttributeReturnValues[$desiredIndex]) ||
                                !str_contains($desiredReturn, 'Mock')
                            ) {
                                return $desiredReturn;
                            }

                            return $this->{$desiredReturn};
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        //set mock for startTime only by given value (carbonMock)
        $expectedResult['startTime'] = $this->{$expectedResult['startTime']};

        $this->assertEquals($expectedResult, $this->getResource()->toArray(Request::create('/')));
    }

    /**
     * @return array<int, mixed>
     */
    public static function toArrayWithoutManagerUsersTestDataProvider(): array
    {
        return [
            'test to array without manager users' => [
                //expected event getAttribute invocation count
                14,
                //expected event getAttribute keys
                [
                    'project_id',
                    'project',
                    'creator',
                    'event_type',
                    'eventName',
                    'start_time',
                    'id',
                    'room_id',
                    'end_time',
                    'allDay',
                    'audience',
                    'is_loud',
                    'subEvents',
                    'shifts'
                ],
                //expected event getAttribute returns
                [
                    1,
                    'projectMock',
                    'creatorMock',
                    'eventTypeMock',
                    'Meeting Rock & Wrestling',
                    'carbonMock',
                    2,
                    3,
                    'carbonMock',
                    true,
                    false,
                    false,
                    [],
                    'shiftsCollectionMock'
                ],
                //expected project getAttribute invocation count
                2,
                //expected project getAttribute keys
                [
                    'name',
                    'managerUsers'
                ],
                //expected project getAttribute returns
                [
                    'Walid Raad',
                    'managerUsersCollectionMock'
                ],
                'state',
                //expected managerUsersCollectionMock all return values
                [],
                //expected ProjectState getAttribute key
                'color',
                //expected ProjectState getAttribute return
                '#3E0717',
                //expected carbon mock utc invocation count
                2,
                //expected carbon mock toIso8601String invocation count,
                2,
                //expected carbon mock toIso8601String returns
                [
                    '2024-07-01T21:59:59+00:00',
                    '2024-07-02T21:59:59+00:00'
                ],
                4,
                //expected eventTypeMock getAttribute keys
                [
                    'hex_code',
                    'hex_code',
                    'name',
                    'abbreviation'
                ],
                //expected eventTypeMock getAttribute returns
                [
                    '#A7A6B1',
                    '#A7A6B1',
                    'Blocker',
                    'BL'
                ],
                //expected shiftsCollectionMock all return values
                [
                    'shiftMock',
                    'shiftMock',
                    'shiftMock'
                ],
                //expected shiftMock getAttribute invocation count
                24,
                //expected shiftMock getAttribute keys
                [
                    'id',
                    'craft',
                    'craft',
                    'craft',
                    'users',
                    'freelancer',
                    'serviceProvider',
                    'shiftsQualifications'
                ],
                //expected shiftMock getAttribute returns
                [
                    1,
                    'craftMock',
                    'craftMock',
                    'craftMock',
                    'shiftRelationCollectionMock',
                    'shiftRelationCollectionMock',
                    'shiftRelationCollectionMock',
                    'shiftsQualificationsCollectionMock'
                ],
                //expected craftMock getAttribute invocation count
                9,
                //expected craftMock getAttribute keys
                [
                    'id',
                    'name',
                    'abbreviation'
                ],
                //expected craftMock getAttribute returns
                [
                    1,
                    'Caldero-Systems GmbH',
                    'CS'
                ],
                //expected shiftRelationCollection count invocation count
                9,
                //expected shiftRelationCollectionMock count return value
                10,
                //expected shiftsQualificationsCollectionMock sum invocation count
                3,
                //expected shiftsQualificationsCollectionMock sum key
                'value',
                //expected shiftsQualificationsCollectionMock sum return
                35,
                //expected creatorMock getAttribute invocation count
                4,
                //expected creatorMock getAttribute keys
                [
                    'id',
                    'profile_photo_url',
                    'first_name',
                    'last_name'
                ],
                //expected creatorMock getAttribute returns
                [
                    1,
                    '/profile-photos/photo-1499996860823-5214fcc65f8f.jpg',
                    'Max',
                    'Müller'
                ],
                //expected result
                [
                    'id' => 2,
                    'projectId' => 1,
                    'roomId' => 3,
                    'created_by' => [
                        'id' => 1,
                        'profile_photo_url' => '/profile-photos/photo-1499996860823-5214fcc65f8f.jpg',
                        'first_name' => 'Max',
                        'last_name' => 'Müller'
                    ],
                    'start' => "2024-07-01T21:59:59+00:00",
                    'startTime' => 'carbonMock',
                    'end' => '2024-07-02T21:59:59+00:00',
                    'allDay' => true,
                    'alwaysEventName' => 'Meeting Rock & Wrestling',
                    'eventName' => 'Meeting Rock & Wrestling',
                    'title' => 'Walid Raad',
                    'event_type_color' => '#A7A6B1',
                    'eventTypeColorBackground' => '#A7A6B133',
                    'eventTypeName' => 'Blocker',
                    'eventTypeAbbreviation' => 'BL',
                    'audience' => false,
                    'isLoud' => false,
                    'projectName' => 'Walid Raad',
                    'projectStateColor' => '#3E0717',
                    'projectLeaders' => [],
                    'subEvents' => [],
                    'shifts' => [
                        [
                            'id' => 1,
                            'craft' => [
                                'id' => 1,
                                'name' => 'Caldero-Systems GmbH',
                                'abbreviation' => 'CS'
                            ],
                            'worker_count' => 30,
                            'max_worker_count' => 35
                        ],
                        [
                            'id' => 1,
                            'craft' => [
                                'id' => 1,
                                'name' => 'Caldero-Systems GmbH',
                                'abbreviation' => 'CS'
                            ],
                            'worker_count' => 30,
                            'max_worker_count' => 35
                        ],
                        [
                            'id' => 1,
                            'craft' => [
                                'id' => 1,
                                'name' => 'Caldero-Systems GmbH',
                                'abbreviation' => 'CS'
                            ],
                            'worker_count' => 30,
                            'max_worker_count' => 35
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider toArrayWithoutManagerUsersTestDataProvider
     * @throws Throwable
     */
    //phpcs:ignore: Generic.Metrics.CyclomaticComplexity.MaxExceeded
    public function testToArrayWithoutManagerUsers(
        int $expectedEventGetAttributeInvocations,
        array $expectedEventGetAttributeKeys,
        array $expectedEventGetAttributeReturnValues,
        int $expectedProjectGetAttributeInvocations,
        array $expectedProjectGetAttributeKeys,
        array $expectedProjectGetAttributeReturnValues,
        string $expectedProjectGetRelationKey,
        array $expectedManagerUsersCollectionMockAllReturnValue,
        string $expectedProjectStateGetAttributeKey,
        string $expectedProjectStateGetAttributeReturnValue,
        int $expectedCarbonMockUtcInvocationCount,
        int $expectedCarbonMockToIso8601StringInvocationCount,
        array $expectedCarbonMockToIso8601StringReturnValues,
        int $expectedEventTypeMockGetAttributeInvocations,
        array $expectedEventTypeMockGetAttributeKeys,
        array $expectedEventTypeMockGetAttributeReturnValues,
        array $expectedShiftsCollectionMockAllReturnValue,
        int $expectedShiftMockGetAttributeInvocations,
        array $expectedShiftMockGetAttributeKeys,
        array $expectedShiftMockGetAttributeReturnValues,
        int $expectedCraftMockGetAttributeInvocations,
        array $expectedCraftMockGetAttributeKeys,
        array $expectedCraftMockGetAttributeReturnValues,
        int $expectedShiftRelationCollectionMockCountInvocationCount,
        int $expectedShiftRelationCollectionMockCountReturnValue,
        int $expectedShiftsQualificationsCollectionMockInvocationCount,
        string $expectedShiftsQualificationsCollectionMockSumKey,
        int $expectedShiftsQualificationsCollectionMockSumReturnValue,
        int $expectedCreatorMockGetAttributeInvocations,
        array $expectedCreatorMockGetAttributeKeys,
        array $expectedCreatorMockGetAttributeReturnValues,
        array $expectedResult
    ): void {
        $this->projectMock->expects($matcher = $this->exactly($expectedProjectGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedProjectGetAttributeKeys,
                    $expectedProjectGetAttributeReturnValues,
                ): string|Collection {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    switch (true) {
                        case (isset($expectedProjectGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedProjectGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            if (
                                !str_contains(
                                    $desiredReturn = $expectedProjectGetAttributeReturnValues[$desiredIndex],
                                    'Mock'
                                )
                            ) {
                                return $desiredReturn;
                            }

                            return $this->{$desiredReturn};
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->projectMock->expects($this->once())
            ->method('getRelation')
            ->with($expectedProjectGetRelationKey)
            ->willReturn($this->projectStateMock);

        $this->projectStateMock->expects($this->once())
            ->method('getAttribute')
            ->with($expectedProjectStateGetAttributeKey)
            ->willReturn($expectedProjectStateGetAttributeReturnValue);

        $this->managerUsersCollectionMock->expects($this->once())
            ->method('all')
            ->willReturn($expectedManagerUsersCollectionMockAllReturnValue);

        $this->carbonMock->expects($this->exactly($expectedCarbonMockUtcInvocationCount))
            ->method('utc')
            ->willReturnSelf();

        $this->carbonMock->expects($matcher = $this->exactly($expectedCarbonMockToIso8601StringInvocationCount))
            ->method('toIso8601String')
            ->willReturnCallback(
                function () use (
                    $matcher,
                    $expectedCarbonMockToIso8601StringReturnValues,
                ): string {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    switch (true) {
                        case (isset($expectedCarbonMockToIso8601StringReturnValues[$desiredIndex])):
                            $this->assertCount(0, func_get_args());
                            return $expectedCarbonMockToIso8601StringReturnValues[$desiredIndex];
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->eventTypeMock->expects($matcher = $this->exactly($expectedEventTypeMockGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedEventTypeMockGetAttributeKeys,
                    $expectedEventTypeMockGetAttributeReturnValues,
                ): string {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    switch (true) {
                        case (isset($expectedEventTypeMockGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedEventTypeMockGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            return $expectedEventTypeMockGetAttributeReturnValues[$desiredIndex];
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->shiftsCollectionMock->expects($this->once())
            ->method('all')
            ->willReturn(
                array_map(
                    function (string $desiredMock): Shift {
                        return $this->{$desiredMock};
                    },
                    $expectedShiftsCollectionMockAllReturnValue
                )
            );

        $this->craftMock->expects($matcher = $this->exactly($expectedCraftMockGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedCraftMockGetAttributeKeys,
                    $expectedCraftMockGetAttributeReturnValues,
                ): mixed {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    $desiredIndex = ($desiredIndex > 2 && $desiredIndex < 6) ?
                        ($desiredIndex - 3) : (
                        ($desiredIndex > 5) ?
                            ($desiredIndex - 6) :
                            $desiredIndex
                        );
                    switch (true) {
                        case (isset($expectedCraftMockGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedCraftMockGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            if (
                                !is_string(
                                    $desiredReturn = $expectedCraftMockGetAttributeReturnValues[$desiredIndex]
                                ) ||
                                !str_contains($desiredReturn, 'Mock')
                            ) {
                                return $desiredReturn;
                            }

                            return $this->{$desiredReturn};
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->shiftRelationCollectionMock
            ->expects($this->exactly($expectedShiftRelationCollectionMockCountInvocationCount))
            ->method('count')
            ->willReturn($expectedShiftRelationCollectionMockCountReturnValue);

        $this->shiftsQualificationsCollectionMock
            ->expects($this->exactly($expectedShiftsQualificationsCollectionMockInvocationCount))
            ->method('sum')
            ->with($expectedShiftsQualificationsCollectionMockSumKey)
            ->willReturn($expectedShiftsQualificationsCollectionMockSumReturnValue);

        $this->shiftMock->expects($matcher = $this->exactly($expectedShiftMockGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedShiftMockGetAttributeKeys,
                    $expectedShiftMockGetAttributeReturnValues,
                ): mixed {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    $desiredIndex = ($desiredIndex > 7 && $desiredIndex < 16) ?
                        ($desiredIndex - 8) : (
                        ($desiredIndex > 15) ?
                            ($desiredIndex - 16) :
                            $desiredIndex
                        );
                    switch (true) {
                        case (isset($expectedShiftMockGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedShiftMockGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            if (
                                !is_string(
                                    $desiredReturn = $expectedShiftMockGetAttributeReturnValues[$desiredIndex]
                                ) ||
                                !str_contains($desiredReturn, 'Mock')
                            ) {
                                return $desiredReturn;
                            }

                            return $this->{$desiredReturn};
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->creatorMock->expects($matcher = $this->exactly($expectedCreatorMockGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedCreatorMockGetAttributeKeys,
                    $expectedCreatorMockGetAttributeReturnValues,
                ): mixed {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    switch (true) {
                        case (isset($expectedCreatorMockGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedCreatorMockGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            return $expectedCreatorMockGetAttributeReturnValues[$desiredIndex];
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->eventMock->expects($matcher = $this->exactly($expectedEventGetAttributeInvocations))
            ->method('getAttribute')
            ->willReturnCallback(
                function (
                    string $getAttributeKey
                ) use (
                    $matcher,
                    $expectedEventGetAttributeKeys,
                    $expectedEventGetAttributeReturnValues,
                ): mixed {
                    $desiredIndex = ($matcher->numberOfInvocations() - 1);
                    switch (true) {
                        case (isset($expectedEventGetAttributeKeys[$desiredIndex])):
                            $this->assertSame(
                                $expectedEventGetAttributeKeys[$desiredIndex],
                                $getAttributeKey
                            );

                            if (
                                !is_string($desiredReturn = $expectedEventGetAttributeReturnValues[$desiredIndex]) ||
                                !str_contains($desiredReturn, 'Mock')
                            ) {
                                return $desiredReturn;
                            }

                            return $this->{$desiredReturn};
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        //set mock for startTime only by given value (carbonMock)
        $expectedResult['startTime'] = $this->{$expectedResult['startTime']};

        $this->assertEquals($expectedResult, $this->getResource()->toArray(Request::create('/')));
    }
}
