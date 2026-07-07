<?php

namespace Tests\Concerns;

use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;

trait InteractsWithInertia
{
    protected function assertInertiaComponent(TestResponse $response, string $component): TestResponse
    {
        return $response->assertInertia(
            fn (AssertableInertia $page) => $page->component($component)
        );
    }

    protected function assertInertiaHasProp(TestResponse $response, string $key): TestResponse
    {
        return $response->assertInertia(
            fn (AssertableInertia $page) => $page->has($key)
        );
    }

    protected function assertInertiaWhere(TestResponse $response, string $key, mixed $expected): TestResponse
    {
        return $response->assertInertia(
            fn (AssertableInertia $page) => $page->where($key, $expected)
        );
    }
}
