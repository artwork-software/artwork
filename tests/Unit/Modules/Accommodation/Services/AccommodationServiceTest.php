<?php

namespace Tests\Unit\Modules\Accommodation\Services;

use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\Accommodation\Services\AccommodationService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AccommodationServiceTest extends TestCase
{
    private AccommodationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AccommodationService::class);
    }

    #[Test]
    public function store_creates_accommodation(): void
    {
        $data = [
            'name' => 'Hotel California',
            'email' => 'info@hotel.test',
            'phone_number' => '+1 555 1234',
            'street' => 'Sunset Blvd',
            'zip_code' => '90028',
            'location' => 'Los Angeles',
            'note' => 'Famous hotel',
        ];

        $accommodation = $this->service->store($data);

        $this->assertInstanceOf(Accommodation::class, $accommodation);
        $this->assertTrue($accommodation->exists);
        $this->assertDatabaseHas('accommodations', ['name' => 'Hotel California']);
    }

    #[Test]
    public function update_modifies_accommodation(): void
    {
        $accommodation = Accommodation::factory()->create(['name' => 'Old Name']);

        $updated = $this->service->update($accommodation, ['name' => 'New Name']);

        $this->assertSame('New Name', $updated->fresh()->name);
        $this->assertDatabaseHas('accommodations', ['id' => $accommodation->id, 'name' => 'New Name']);
    }

    #[Test]
    public function destroy_removes_accommodation(): void
    {
        $accommodation = Accommodation::factory()->create();

        $this->service->destroy($accommodation);

        $this->assertDatabaseMissing('accommodations', ['id' => $accommodation->id]);
    }
}
