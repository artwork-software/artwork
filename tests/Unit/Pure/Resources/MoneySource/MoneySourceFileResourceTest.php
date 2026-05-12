<?php

namespace Tests\Unit\Pure\Resources\MoneySource;

use Artwork\Modules\MoneySource\Http\Resources\MoneySourceFileResource;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MoneySourceFileResourceTest extends TestCase
{
    #[Test]
    public function it_transforms_file_to_array(): void
    {
        $model = (object) [
            'id' => 13,
            'name' => 'invoice.pdf',
            'basename' => 'invoice',
            'money_source_id' => 99,
            'created_at' => '2026-01-01 00:00:00',
            'comments' => new Collection(),
        ];

        $array = (new MoneySourceFileResource($model))->toArray(new Request());

        $this->assertSame(13, $array['id']);
        $this->assertSame('invoice.pdf', $array['name']);
        $this->assertSame('invoice', $array['basename']);
        $this->assertSame(99, $array['money_source_id']);
        $this->assertSame('2026-01-01 00:00:00', $array['created_at']);
        $this->assertArrayHasKey('comments', $array);
    }

    #[Test]
    public function array_has_expected_keys(): void
    {
        $array = (new MoneySourceFileResource((object) [
            'id' => 1, 'name' => '', 'basename' => '', 'money_source_id' => 0,
            'created_at' => null, 'comments' => new Collection(),
        ]))->toArray(new Request());

        foreach (['id', 'name', 'basename', 'money_source_id', 'created_at', 'comments'] as $key) {
            $this->assertArrayHasKey($key, $array);
        }
    }
}
