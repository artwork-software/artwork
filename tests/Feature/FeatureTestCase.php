<?php

namespace Tests\Feature;

use Artwork\Core\Validation\Rules\PublicUrlRule;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithInertia;
use Tests\TestCase;

abstract class FeatureTestCase extends TestCase
{
    use InteractsWithInertia;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Mail::fake();
        Queue::fake();
        Bus::fake();
        Storage::fake('local');

        // Kein echtes DNS in Feature-Tests: Beispiel-Hosts (*.example.test) gelten als öffentlich.
        // Tests, die private Ziele prüfen wollen, setzen einen eigenen Resolver.
        PublicUrlRule::resolveUsing(static fn (): array => ['203.0.113.10']);
    }

    protected function tearDown(): void
    {
        PublicUrlRule::resolveUsing(null);

        parent::tearDown();
    }
}
