<?php

namespace Tests\Unit\Artwork\Modules\Vacation\Https\Requests;

use Artwork\Modules\Vacation\Https\Requests\CreateVacationRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CreateVacationRequestTest extends TestCase
{
    public static function validationDataProvider(): array
    {
        return [
            'requestShouldFailWhenNoDataIsProvided' => [
                'requestData' => [],
                'expectedFailedKeys' => ['date', 'type']
            ],
            'requestShouldFailWhenInvalidDataIsProvided' => [
                'requestData' => [
                    'start_time' => 'invalid',
                    'end_time' => 'invalid',
                    'date' => 'invalid',
                    'type' => 123,
                    'full_day' => 'invalid',
                    'comment' => str_repeat('a', 21),
                    'is_series' => 'invalid',
                    'series_repeat' => 123,
                    'series_repeat_until' => 'invalid',
                ],
                'expectedFailedKeys' => [
                  'type', 'full_day', 'comment', 'is_series', 'series_repeat', 'series_repeat_until'
                ]
            ],
            'requestShouldPassWhenValidDataIsProvided' => [
                'requestData' => [
                    'start_time' => '10:00',
                    'end_time' => '18:00',
                    'date' => '2022-12-31',
                    'type' => 'vacation',
                    'full_day' => true,
                    'comment' => 'This is a comment',
                    'is_series' => true,
                    'series_repeat' => 'weekly',
                    'series_repeat_until' => '2023-01-31',
                ],
                'expectedFailedKeys' => []
            ],
        ];
    }

    /**
     * @dataProvider validationDataProvider
     */
    public function testValidation($requestData, $expectedFailedKeys): void
    {
        $request = new CreateVacationRequest();

        $validator = Validator::make($requestData, $request->rules());

        $this->assertEquals($expectedFailedKeys, $validator->errors()->keys());
    }
}
