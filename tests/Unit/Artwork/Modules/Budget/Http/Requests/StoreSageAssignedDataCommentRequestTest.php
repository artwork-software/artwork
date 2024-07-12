<?php

namespace Tests\Unit\Artwork\Modules\Budget\Http\Requests;

use Artwork\Modules\Budget\Http\Requests\StoreSageAssignedDataCommentRequest;
use PHPUnit\Framework\TestCase;

class StoreSageAssignedDataCommentRequestTest extends TestCase
{
    public function testRules(): void
    {
        $this->assertSame(
            [
                'userId' => 'int|exists:users,id',
                'sageAssignedDataId' => 'int|exists:sage_assigned_data,id',
                'comment' => 'string'
            ],
            (new StoreSageAssignedDataCommentRequest())->rules()
        );
    }
}
