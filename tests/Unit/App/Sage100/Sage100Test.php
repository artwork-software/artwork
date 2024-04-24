<?php

namespace Tests\Unit\App\Sage100;

use Artwork\Modules\Sage100\Clients\Sage100Client;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Sage100Test extends TestCase
{
    public function testGetData(): void
    {
        Http::fake([
            '*' => Http::response(['$resources' => ['data']], 200),
        ]);

        $sage100 = new Sage100Client('http://example.com', '/endpoint', 'user', 'password');
        $data = $sage100->getData();

        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
    }

    public function testTestConnection(): void
    {
        Http::fake([
            '*' => Http::response([], 200),
        ]);

        $sage100 = new Sage100Client('http://example.com', '/endpoint', 'user', 'password');
        $result = $sage100->testConnection();

        $this->assertTrue($result);
    }
}
