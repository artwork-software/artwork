<?php

namespace Tests\Unit\Artwork\Core\Database\Models;

use Artwork\Core\Database\Models\Model;
use Tests\TestCase;

class ModelTest extends TestCase
{
    public function testBelongsTo(): void
    {
        $model = new Model();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('All params of belongsTo have to be used');

        $model->belongsTo('related');
    }
}
