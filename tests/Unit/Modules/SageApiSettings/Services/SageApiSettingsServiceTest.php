<?php

namespace Tests\Unit\Modules\SageApiSettings\Services;

use Artwork\Modules\SageApiSettings\Models\SageApiSettings;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SageApiSettingsServiceTest extends TestCase
{
    private SageApiSettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SageApiSettingsService::class);
    }

    #[Test]
    public function get_first_returns_null_when_none_exist(): void
    {
        $this->assertNull($this->service->getFirst());
    }

    #[Test]
    public function get_first_returns_existing_settings(): void
    {
        SageApiSettings::create([
            'host' => 'https://example.test',
            'endpoint' => '/api',
            'user' => 'sage',
            'password' => 'secret',
            'enabled' => true,
        ]);

        $result = $this->service->getFirst();

        $this->assertNotNull($result);
        $this->assertSame('https://example.test', $result->host);
    }

    #[Test]
    public function update_booking_date_persists_carbon_date(): void
    {
        SageApiSettings::create([
            'host' => 'https://example.test',
            'endpoint' => '/api',
            'user' => 'sage',
            'password' => 'secret',
            'enabled' => true,
        ]);

        $this->service->updateBookingDate(Carbon::parse('2024-05-15'));

        $this->assertDatabaseHas('sage_api_settings', [
            'host' => 'https://example.test',
            'bookingDate' => '2024-05-15',
        ]);
    }
}
