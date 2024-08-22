<?php

namespace Tests\Unit\Artwork\Modules\GlobalNotification\Http\Requests;

use Artwork\Modules\GlobalNotification\Http\Requests\StoreGlobalNotificationRequest;
use PHPUnit\Framework\TestCase;

class UpdateGlobalNotificationRequestTest extends TestCase
{
    public function testRules(): void
    {
        self::assertSame(
            [
                'notificationName' => 'required|string',
                'notificationDeadlineDate' => 'string|nullable',
                'notificationDeadlineTime' => 'string|nullable',
                'notificationDescription' => 'string|nullable',
            ],
            (new StoreGlobalNotificationRequest())->rules()
        );
    }
}
