<?php

namespace Tests\Unit\Artwork\Modules\Vacation\Https\Requests;

use Artwork\Modules\Vacation\Https\Requests\UpdateVacationRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateVacationRequestTest extends TestCase
{
    public static function validationDataProvider(): array
    {
        return [
            'requestShouldFailWhenNoDataIsProvided' => [
                'requestData' => [],
                'expectedFailedKeys' => ['id', 'date', 'type', 'type_before_update']
            ],
            'requestShouldFailWhenInvalidDataIsProvided' => [
                'requestData' => [
                    'id' => 'invalid',
                    'start_time' => 'invalid',
                    'end_time' => 'invalid',
                    'date' => 'invalid',
                    'type' => 123,
                    'type_before_update' => 123,
                    'full_day' => 'invalid',
                    'comment' => str_repeat('a', 21),
                    'is_series' => 'invalid',
                    'series_repeat' => 123,
                    'series_repeat_until' => 'invalid',
                ],
                'expectedFailedKeys' => [
                    'id',
                    'full_day',
                    'comment',
                    'is_series',
                    'series_repeat',
                    'series_repeat_until',
                    'type',
                    'type_before_update',
                ]
            ],
            'requestShouldPassWhenValidDataIsProvided' => [
                'requestData' => [
                    'id' => 1,
                    'start_time' => '10:00',
                    'end_time' => '18:00',
                    'date' => '2022-12-31',
                    'type' => 'vacation',
                    'type_before_update' => 'vacation',
                    'full_day' => true,
                    'comment' => 'This is a comment',
                    'is_series' => true,
                    'series_repeat' => 'weekly',
                    'series_repeat_until' => '2023-01-31',
                ],
                'expectedFailedKeys' => ['id']
            ],
        ];
    }

    /**
     * @dataProvider validationDataProvider
     */
    public function testValidation($requestData, $expectedFailedKeys): void
    {
        $request = new UpdateVacationRequest();

        $validator = Validator::make($requestData, $request->rules());

        $this->assertEquals($expectedFailedKeys, $validator->errors()->keys());
    }
}
