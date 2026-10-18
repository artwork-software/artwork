<?php

namespace Tests\Unit\Core;

use Artwork\Core\Sentry\SentryEventScrubber;
use PHPUnit\Framework\Attributes\Test;
use Sentry\Event;
use Tests\Unit\UnitTestCase;

final class SentryEventScrubberTest extends UnitTestCase
{
    #[Test]
    public function passwords_and_two_factor_codes_are_removed_from_the_request_body(): void
    {
        $event = Event::createEvent();
        $event->setRequest([
            'url' => 'https://artwork.test/login',
            'method' => 'POST',
            'data' => [
                'email' => 'person@example.com',
                'password' => 'secret',
                'Password_Confirmation' => 'secret',
                'current_password' => 'old-secret',
                'code' => '123456',
                'recovery_code' => 'abcd-efgh',
                'user' => ['password' => 'nested-secret', 'name' => 'Person'],
                'remember' => true,
            ],
        ]);

        $scrubbed = SentryEventScrubber::beforeSend($event);

        $this->assertNotNull($scrubbed);
        $data = $scrubbed->getRequest()['data'];
        foreach (['password', 'Password_Confirmation', 'current_password', 'code', 'recovery_code'] as $key) {
            $this->assertSame(SentryEventScrubber::FILTERED, $data[$key], $key);
        }
        $this->assertSame(['password' => SentryEventScrubber::FILTERED, 'name' => 'Person'], $data['user']);
        $this->assertSame('person@example.com', $data['email']);
        $this->assertTrue($data['remember']);
        $this->assertSame('https://artwork.test/login', $scrubbed->getRequest()['url']);
    }

    #[Test]
    public function unparsed_bodies_naming_a_sensitive_field_are_dropped(): void
    {
        $event = Event::createEvent();
        $event->setRequest(['data' => '{"password": "secret"']);

        $this->assertSame(SentryEventScrubber::FILTERED, SentryEventScrubber::beforeSend($event)?->getRequest()['data']);

        $harmless = Event::createEvent();
        $harmless->setRequest(['data' => 'plain text']);
        $this->assertSame('plain text', SentryEventScrubber::beforeSend($harmless)?->getRequest()['data']);
    }

    #[Test]
    public function events_without_request_body_pass_unchanged(): void
    {
        $event = Event::createEvent();
        $event->setRequest(['url' => 'https://artwork.test/']);

        $this->assertSame(['url' => 'https://artwork.test/'], SentryEventScrubber::beforeSend($event)?->getRequest());
    }

    #[Test]
    public function the_scrubber_is_configured_as_before_send(): void
    {
        $config = require __DIR__ . '/../../../config/sentry.php';

        $this->assertSame([SentryEventScrubber::class, 'beforeSend'], $config['before_send']);
        $this->assertTrue(is_callable($config['before_send']));
    }
}
