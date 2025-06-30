<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Requests\Column;

use Artwork\Modules\InventoryManagement\Enums\CraftsInventoryColumnTypeEnum;
use Artwork\Modules\InventoryManagement\Http\Requests\Column\CreateCraftsInventoryColumnRequest;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ConditionalRules;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\RequiredIf;
use Tests\TestCase;

class CreateCraftsInventoryColumnRequestTest extends TestCase
{
    /**
     * Create a test subclass of CreateCraftsInventoryColumnRequest with select column type
     */
    private function createSelectColumnRequest(): CreateCraftsInventoryColumnRequest
    {
        return new class extends CreateCraftsInventoryColumnRequest {
            public function rules(): array
            {
                // Override to simulate a request with type.id = 3 (SELECT)
                return [
                    'name' => 'required|string',
                    'type' => ['required', 'array:id,value'],
                    'type.*.id' => Rule::enum(CraftsInventoryColumnTypeEnum::class),
                    'typeOptions' => [
                        'array',
                        Rule::requiredIf(true), // Force condition to true
                        Rule::when(true, ['min:1']),
                        Rule::when(false, ['min:0'])
                    ],
                    'typeOptions.*' => 'required|string',
                    'defaultOption' => 'nullable|string'
                ];
            }
        };
    }

    /**
     * Create a test subclass of CreateCraftsInventoryColumnRequest with non-select column type
     */
    private function createNonSelectColumnRequest(): CreateCraftsInventoryColumnRequest
    {
        return new class extends CreateCraftsInventoryColumnRequest {
            public function rules(): array
            {
                // Override to simulate a request with type.id != 3 (not SELECT)
                return [
                    'name' => 'required|string',
                    'type' => ['required', 'array:id,value'],
                    'type.*.id' => Rule::enum(CraftsInventoryColumnTypeEnum::class),
                    'typeOptions' => [
                        'array',
                        Rule::requiredIf(false), // Force condition to false
                        Rule::when(false, ['min:1']),
                        Rule::when(true, ['min:0'])
                    ],
                    'typeOptions.*' => 'required|string',
                    'defaultOption' => 'nullable|string'
                ];
            }
        };
    }

    public function testRules(): void
    {
        // Test with required select option types
        $selectRequest = $this->createSelectColumnRequest();
        $rules = $selectRequest->rules();

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
            ['min:1'],
            $rules['typeOptions'][2]->rules()
        );
        self::assertSame(
            ['min:0'],
            $rules['typeOptions'][3]->rules()
        );
        self::assertTrue($rules['typeOptions'][1]->condition);

        self::assertArrayHasKey('typeOptions.*', $rules);
        self::assertSame('required|string', $rules['typeOptions.*']);

        self::assertArrayHasKey('defaultOption', $rules);
        self::assertSame('nullable|string', $rules['defaultOption']);

        // Test without required select option types
        $nonSelectRequest = $this->createNonSelectColumnRequest();
        $rules = $nonSelectRequest->rules();

        self::assertFalse($rules['typeOptions'][1]->condition);
    }
}
