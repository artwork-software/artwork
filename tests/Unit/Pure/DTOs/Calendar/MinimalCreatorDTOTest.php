<?php

namespace Tests\Unit\Pure\DTOs\Calendar;

use Artwork\Modules\Calendar\DTO\MinimalCreatorDTO;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MinimalCreatorDTOTest extends TestCase
{
    #[Test]
    public function it_creates_from_array(): void
    {
        $dto = MinimalCreatorDTO::from([
            'id' => 7,
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'profile_photo_url' => 'https://example.com/ada.png',
        ]);

        $this->assertSame(7, $dto->id);
        $this->assertSame('Ada', $dto->first_name);
        $this->assertSame('Lovelace', $dto->last_name);
        $this->assertSame('https://example.com/ada.png', $dto->profile_photo_url);
    }

    #[Test]
    public function profile_photo_url_is_nullable_and_defaults_to_null(): void
    {
        $dto = MinimalCreatorDTO::from([
            'id' => 1,
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
        ]);

        $this->assertNull($dto->profile_photo_url);
    }

    #[Test]
    public function to_array_returns_expected_structure(): void
    {
        $dto = new MinimalCreatorDTO(1, 'A', 'B', null);
        $array = $dto->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('first_name', $array);
        $this->assertArrayHasKey('last_name', $array);
        $this->assertArrayHasKey('profile_photo_url', $array);
        $this->assertSame(1, $array['id']);
        $this->assertSame('A', $array['first_name']);
        $this->assertNull($array['profile_photo_url']);
    }

    #[Test]
    public function constructor_promotes_typed_public_properties(): void
    {
        $dto = new MinimalCreatorDTO(99, 'X', 'Y');
        $this->assertSame(99, $dto->id);
        $this->assertSame('X', $dto->first_name);
        $this->assertSame('Y', $dto->last_name);
        $this->assertNull($dto->profile_photo_url);
    }
}
