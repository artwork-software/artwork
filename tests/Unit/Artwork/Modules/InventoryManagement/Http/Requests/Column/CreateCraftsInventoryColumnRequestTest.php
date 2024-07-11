<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Requests\Column;

use Artwork\Modules\InventoryManagement\Enums\CraftsInventoryColumnTypeEnum;
use Artwork\Modules\InventoryManagement\Http\Requests\Column\CreateCraftsInventoryColumnRequest;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\RequiredIf;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

class CreateCraftsInventoryColumnRequestTest extends TestCase
{
    /** @return array<int, array<int, mixed>> */
    public static function ruleTestDataProvider(): array
    {
        return [
            [
                new CreateCraftsInventoryColumnRequest(),
                new CreateCraftsInventoryColumnRequest()
            ]
        ];
    }

    /**
     * @dataProvider ruleTestDataProvider
     */
    public function testRules(
        CreateCraftsInventoryColumnRequest $firstRequest,
        CreateCraftsInventoryColumnRequest $secondRequest
    ): void {
        //test with required select option types
        $firstRequest->request = $this->getMockBuilder(ParameterBag::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['all'])
            ->getMock();
        $firstRequest->request->expects(self::once())
            ->method('all')
            ->willReturn([
                'type' => [
                    'id' => 3
                ]
            ]);

        $rules = $firstRequest->rules();

        self::assertArrayHasKey('name', $rules);
        self::assertSame('required|string', $rules['name']);

        self::assertArrayHasKey('type', $rules);
        self::assertSame(
            [
                'required',
                'array:id,value'
            ],
            $rules['type']
        );

        self::assertArrayHasKey('type.*.id', $rules);
        self::assertInstanceOf(Enum::class, $rules['type.*.id']);
        self::assertSame(
            CraftsInventoryColumnTypeEnum::class,
            //get protected property by binding Closure to desired object and calling it
            Closure::bind(
                fn() => $this->type,
                $rules['type.*.id'],
                $rules['type.*.id']
            )()
        );

        self::assertArrayHasKey('selectOptions', $rules);
        self::assertInstanceOf(RequiredIf::class, $rules['selectOptions'][0]);
        self::assertSame('array', $rules['selectOptions'][1]);
        self::assertSame('min:1', $rules['selectOptions'][2]);
        self::assertTrue($rules['selectOptions'][0]->condition);

        self::assertArrayHasKey('selectOptions.*', $rules);
        self::assertSame('required|string', $rules['selectOptions.*']);

        self::assertArrayHasKey('defaultOption', $rules);
        self::assertSame('nullable|string', $rules['defaultOption']);

        //test without required select option types
        $secondRequest->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['all'])
            ->getMock();
        $secondRequest->request->expects(self::once())
            ->method('all')
            ->willReturn([
                'type' => [
                    'id' => 1
                ]
            ]);

        $rules = $secondRequest->rules();

        self::assertFalse($rules['selectOptions'][0]->condition);
    }
}
