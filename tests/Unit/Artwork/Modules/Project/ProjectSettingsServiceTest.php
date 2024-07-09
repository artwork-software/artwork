<?php

namespace Tests\Unit\Artwork\Modules\Project;

use Artwork\Modules\Project\Http\Requests\ProjectCreateSettingsUpdateRequest;
use Artwork\Modules\Project\Models\ProjectCreateSettings;
use Artwork\Modules\Project\Services\ProjectSettingsService;
use AssertionError;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class ProjectSettingsServiceTest extends TestCase
{
    private readonly ProjectSettingsService $projectSettingsService;

    protected function setUp(): void
    {
        //setup is called before each test is executed (means fresh instances)

        //get UUT (unit under test)
        /** @var ProjectSettingsService $projectSettingsService */
        $this->projectSettingsService = new ProjectSettingsService();
    }

    /**
     * @return array<array<int, mixed>>
     */
    public static function storeTestDataProvider(): array
    {
        //yield could also be used
        //each array in the array is used for exactly one run. The first index is the first parameter -> $requestKeys,
        //the second index is the second parameter -> $requestReturns, passed to testStore
        $expectedRequestKeys = [
            'attributes',
            'state',
            'managers',
            'cost_center',
            'budget_deadline'
        ];
        return [
            'save model with true values' => [
                $expectedRequestKeys,
                [
                    true,
                    true,
                    true,
                    true,
                    true
                ]
            ],
            'save model with false values' => [
                $expectedRequestKeys,
                [
                    false,
                    false,
                    false,
                    false,
                    false
                ]
            ],
            //just an example, not necessary as true and false are already tested
            'save model with mixed values' => [
                $expectedRequestKeys,
                [
                    true,
                    false,
                    true,
                    false,
                    true
                ]
            ],
        ];
    }

    /**
     * @dataProvider storeTestDataProvider
     * @throws Exception
     */
    public function testStore(array $requestKeys, $requestReturns): void
    {
        //create mock
        $requestMock = $this->getMockBuilder(ProjectCreateSettingsUpdateRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['boolean', 'string', 'integer'])
            ->getMock();

        //add expectations
        //how often function boolean() is called? which parameters are used, what is returned on each call?
        $requestMock->expects($matcher = self::exactly(5))
            ->method('boolean')
            ->willReturnCallback(
                function (string $key) use ($matcher, $requestKeys, $requestReturns): bool {
                    //assert function argument on each call and return desired value for it
                    switch ($matcher->numberOfInvocations()) {
                        //each case assert the parameter which was passed to boolean function call
                        //and return value which shall be returned by boolean function call
                        case 1:
                            $this->assertSame($requestKeys[0], $key);
                            return $requestReturns[0];
                        case 2:
                            $this->assertSame($requestKeys[1], $key);
                            return $requestReturns[1];
                        case 3:
                            $this->assertSame($requestKeys[2], $key);
                            return $requestReturns[2];
                        case 4:
                            $this->assertSame($requestKeys[3], $key);
                            return $requestReturns[3];
                        case 5:
                            $this->assertSame($requestKeys[4], $key);
                            return $requestReturns[4];
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        //create mock
        $settingsModelMock = $this->getMockBuilder(ProjectCreateSettings::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save'])
            ->getMock();

        //add expectation that save method is called exactly one time
        $settingsModelMock->expects(self::once())
            ->method('save');

        //run method with configured mocks
        $this->projectSettingsService->store($requestMock, $settingsModelMock);

        //now assert that properties are properly placed inside the model (returned by willReturnCallback)
        foreach ($requestKeys as $index => $key) {
            self::assertSame($requestReturns[$index], $settingsModelMock->{$key});
        }
    }
}
