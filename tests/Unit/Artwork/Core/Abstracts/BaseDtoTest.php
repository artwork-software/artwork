<?php

namespace Tests\Unit\Artwork\Core\Abstracts;

use Tests\TestCase;
use Artwork\Core\Abstracts\BaseDto;

class BaseDtoTest extends TestCase
{
    public function testNewInstance(): void
    {
        $class = new class extends BaseDto {
            public string $testProperty;
        };
        $baseDto = $class::newInstance();
        $this->assertInstanceOf(BaseDto::class, $baseDto);
    }

    public function testJsonSerialize(): void
    {
        $baseDto = new class(['testProperty' => 'testValue']) extends BaseDto {};
        $expectedResult = ['test_property' => 'testValue'];

        $this->assertEquals($expectedResult, $baseDto->jsonSerialize());
    }

    public function testToArray(): void
    {
        $baseDto = new class(['testProperty' => 'testValue']) extends  BaseDto{};
        $expectedResult = ['test_property' => 'testValue'];

        $this->assertEquals($expectedResult, $baseDto->toArray());
    }
}
