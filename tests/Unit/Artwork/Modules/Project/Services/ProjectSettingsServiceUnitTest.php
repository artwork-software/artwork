<?php

namespace Tests\Unit\Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Http\Requests\ProjectCreateSettingRequest;
use Artwork\Modules\Project\Models\ProjectCreateSettings;
use Artwork\Modules\Project\Services\ProjectSettingsService;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class ProjectSettingsServiceUnitTest extends TestCase
{

    /**
     * @return array<array<int, mixed>>
     */
    public static function storeTestDataProvider(): array
    {
        return [
            [
                [
                    'attributes',
                    'state',
                    'managers',
                    'cost_center',
                    'budget_deadline'
                ],
                [
                    true,
                    true,
                    true,
                    true,
                    true
                ]
            ]
        ];
    }

    /**
     * @dataProvider storeTestDataProvider
     * @throws Exception|BindingResolutionException
     */
    public function testStore(array $requestKeys, $requestReturns): void
    {
        //get UUT (unit under test)
        /** @var ProjectSettingsService $projectSettingsService */
        $projectSettingsService = app()->make(ProjectSettingsService::class);

        //create mock
        $requestMock = $this->getMockBuilder(ProjectCreateSettingRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['boolean'])
            ->getMock();

        //add expectations
        $matcher = self::exactly(5);
        $requestMock->expects($matcher)
            ->method('boolean')
            ->willReturnCallback(function (string $key) use ($matcher, $requestKeys): void {
                //assert function argument on each call
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals($requestKeys[0], $key),
                    2 => $this->assertEquals($requestKeys[1], $key),
                    3 => $this->assertEquals($requestKeys[2], $key),
                    4 => $this->assertEquals($requestKeys[3], $key),
                    5 => $this->assertEquals($requestKeys[4], $key),
                };
            })->willReturnOnConsecutiveCalls(...$requestReturns);

        //create mock
        $settingsModelMock = $this->getMockBuilder(ProjectCreateSettings::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save'])
            ->getMock();

        //add expectations
        $settingsModelMock->expects(self::once())
            ->method('save');

        //run method with configured mocks
        $projectSettingsService->store($requestMock, $settingsModelMock);

        //now assert that properties are properly placed inside the model
        //not necessary (usually part of FeatureTest) but more assertions = more security
        foreach ($requestKeys as $index => $key) {
            self::assertEquals($requestReturns[$index], $settingsModelMock->{$key});
        }
    }
}
