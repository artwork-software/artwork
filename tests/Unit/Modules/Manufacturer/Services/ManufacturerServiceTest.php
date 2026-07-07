<?php

namespace Tests\Unit\Modules\Manufacturer\Services;

use Artwork\Modules\Manufacturer\Models\Manufacturer;
use Artwork\Modules\Manufacturer\Services\ManufacturerService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ManufacturerServiceTest extends TestCase
{
    private ManufacturerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ManufacturerService::class);
    }

    #[Test]
    public function get_all_returns_paginator(): void
    {
        Manufacturer::factory()->count(15)->create();

        $result = $this->service->getAll(null, 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertSame(15, $result->total());
        $this->assertSame(10, $result->perPage());
    }

    #[Test]
    public function store_creates_manufacturer(): void
    {
        $data = [
            'name' => 'Acme GmbH',
            'address' => 'Musterstr. 1',
            'website' => 'https://acme.test',
            'customer_number' => '12345',
            'contact_person' => 'Max Mustermann',
            'phone' => '+49 123 4567',
            'email' => 'kontakt@acme.test',
        ];

        $manufacturer = $this->service->store($data);

        $this->assertInstanceOf(Manufacturer::class, $manufacturer);
        $this->assertDatabaseHas('manufacturers', ['name' => 'Acme GmbH', 'email' => 'kontakt@acme.test']);
    }

    #[Test]
    public function update_changes_manufacturer_fields(): void
    {
        $manufacturer = Manufacturer::factory()->create(['name' => 'Old']);

        $this->service->update($manufacturer, ['name' => 'New']);

        $this->assertSame('New', $manufacturer->fresh()->name);
    }

    #[Test]
    public function delete_removes_manufacturer(): void
    {
        $manufacturer = Manufacturer::factory()->create();

        $this->service->delete($manufacturer);

        $this->assertDatabaseMissing('manufacturers', ['id' => $manufacturer->id]);
    }
}
