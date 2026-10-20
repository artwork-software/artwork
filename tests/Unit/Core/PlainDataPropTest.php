<?php

namespace Tests\Unit\Core;

use Artwork\Core\Http\Inertia\PlainDataProp;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * PlainDataProp darf die Ausgabe nicht verändern und muss für Inertia undurchlässig bleiben (kein Array,
 * kein Arrayable) — sonst läuft die rekursive Prop-Auflösung wieder über jeden einzelnen Wert.
 */
final class PlainDataPropTest extends TestCase
{
    #[Test]
    public function serializes_exactly_like_the_wrapped_value(): void
    {
        $value = [
            ['day' => '2026-10-01', 'rooms' => [3 => ['shifts' => [['id' => 1]]]]],
            'nested' => new Collection([['id' => 2], ['id' => 3]]),
            'empty' => [],
        ];

        $this->assertSame(json_encode($value), json_encode(new PlainDataProp($value)));
    }

    #[Test]
    public function is_not_resolved_recursively_by_inertia(): void
    {
        $prop = new PlainDataProp(['a' => 1]);

        $this->assertNotInstanceOf(Arrayable::class, $prop);
        $this->assertIsNotArray($prop);
    }
}
