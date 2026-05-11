<?php

namespace Tests\Unit\Pure\Enums\ArtistResidency;

use Artwork\Modules\ArtistResidency\Enums\TypOfRoom;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

final class TypOfRoomTest extends UnitTestCase
{
    #[Test]
    public function it_has_appartement_case(): void
    {
        $this->assertSame('appartement', TypOfRoom::APPARTEMENT->value);
    }

    #[Test]
    public function it_has_suite_case(): void
    {
        $this->assertSame('suite', TypOfRoom::SUITE->value);
    }

    #[Test]
    public function it_has_standard_room_case(): void
    {
        $this->assertSame('standard_room', TypOfRoom::STANDARD_ROOM->value);
    }

    #[Test]
    public function it_has_double_room_case(): void
    {
        $this->assertSame('double_room', TypOfRoom::DOUBLE_ROOM->value);
    }

    #[Test]
    public function it_has_chambre_de_hotel_case(): void
    {
        $this->assertSame('chambre_de_hotel', TypOfRoom::CHAMBRE_DE_HOTEL->value);
    }

    #[Test]
    public function it_has_at_least_eighteen_cases(): void
    {
        $this->assertGreaterThanOrEqual(18, count(TypOfRoom::cases()));
    }

    #[Test]
    public function all_values_are_unique(): void
    {
        $values = array_map(static fn (TypOfRoom $case): string => $case->value, TypOfRoom::cases());
        $this->assertSame(count($values), count(array_unique($values)));
    }

    #[Test]
    public function try_from_returns_null_for_unknown(): void
    {
        $this->assertNull(TypOfRoom::tryFrom('villa'));
    }
}
