<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Requests\Column;

use Artwork\Modules\Inventory\Enums\CraftsInventoryColumnTypeEnum;
use Artwork\Modules\InventoryManagement\Http\Requests\Column\CreateCraftsInventoryColumnRequest;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ConditionalRules;
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
                new CreateCraftsInventoryColumnRequest(),
                //typeOptions validation rules when required
                ['min:1'],
                //typeOptions validation rules when not required
                ['min:0']
            ]
        ];
    }

    /**
     * @dataProvider ruleTestDataProvider
     */
    public function testRules(
        CreateCraftsInventoryColumnRequest $firstRequest,
        CreateCraftsInventoryColumnRequest $secondRequest,
        array $requiredTypeOptionsRules,
        array $notRequiredTypeOptionsRules
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

        self::assertArrayHasKey('typeOptions', $rules);
        self::assertSame('array', $rules['typeOptions'][0]);
        self::assertInstanceOf(RequiredIf::class, $rules['typeOptions'][1]);
        self::assertInstanceOf(ConditionalRules::class, $rules['typeOptions'][2]);
        self::assertInstanceOf(ConditionalRules::class, $rules['typeOptions'][3]);
        self::assertSame(
            $requiredTypeOptionsRules,
            $rules['typeOptions'][2]->rules()
        );
        self::assertSame(
            $notRequiredTypeOptionsRules,
            $rules['typeOptions'][3]->rules()
        );
        self::assertTrue($rules['typeOptions'][1]->condition);

        self::assertArrayHasKey('typeOptions.*', $rules);
        self::assertSame('required|string', $rules['typeOptions.*']);

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

        self::assertFalse($rules['typeOptions'][1]->condition);
    }
}
