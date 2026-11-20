<?php

namespace Tests\Unit\Core;

use Sentry\ExceptionDataBag;
use Artwork\Core\Sentry\SentryEventScrubber;
use PHPUnit\Framework\Attributes\Test;
use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\Tracing\Span;
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

    #[Test]
    public function tokens_are_removed_from_url_paths_and_query_strings(): void
    {
        $token = str_repeat('aB3', 21) . 'x';
        $uuid = '0b8f5a52-6c1e-4b7e-9a57-2f3f1f0c9d11';
        $cases = [
            "https://artwork.test/external/login/$token" => 'https://artwork.test/external/login/[Filtered]',
            "/users/invitations/accept?token=$token&email=a%40b.de"
                => '/users/invitations/accept?token=[Filtered]&email=a%40b.de',
            "https://artwork.test/reset-password?email=a%40b.de&token=$token"
                => 'https://artwork.test/reset-password?email=a%40b.de&token=[Filtered]',
            "/reset-password/$token?email=a%40b.de" => '/reset-password/[Filtered]?email=a%40b.de',
            "/setup?token=$token" => '/setup?token=[Filtered]',
            "https://artwork.test/calendar/abo/$uuid" => 'https://artwork.test/calendar/abo/[Filtered]',
            "/shift/calendar/abo/$uuid" => '/shift/calendar/abo/[Filtered]',
            "token=$token&page=2" => 'token=[Filtered]&page=2',
            "/api/app/v1/files/7?expires=1&user=3&signature=$token"
                => '/api/app/v1/files/7?expires=1&user=3&signature=[Filtered]',
            // Routen-Platzhalter, Verwaltungsrouten der Abos und gewöhnliche URLs bleiben lesbar
            '/external/login/{token}' => '/external/login/{token}',
            '/external/login' => '/external/login',
            '/calendar/abo/create' => '/calendar/abo/create',
            '/shift/calendar/abo/12/renew' => '/shift/calendar/abo/12/renew',
            'https://artwork.test/projects/5/tab/2?page=3' => 'https://artwork.test/projects/5/tab/2?page=3',
            'select * from users where token = ?' => 'select * from users where token = ?',
        ];

        foreach ($cases as $input => $expected) {
            $this->assertSame($expected, SentryEventScrubber::scrubUrl($input), $input);
        }
    }

    #[Test]
    public function tokens_in_urls_are_removed_everywhere_in_an_event(): void
    {
        $token = str_repeat('Zz9', 21) . 'q';
        $event = Event::createEvent();
        $event->setRequest([
            'url' => "https://artwork.test/users/invitations/accept?token=$token",
            'query_string' => "token=$token",
            'headers' => ['referer' => ["https://artwork.test/external/login/$token"], 'host' => ['artwork.test']],
        ]);
        $event->setTransaction("/external/login/$token");
        $event->setTags(['url' => "/reset-password/$token"]);
        $event->setExtra(['previous' => "https://artwork.test/setup?token=$token"]);
        $event->setContext('trace', ['data' => ['url' => "/calendar/abo/$token"], 'op' => 'http.server']);
        $event->setBreadcrumb([
            new Breadcrumb(
                Breadcrumb::LEVEL_INFO,
                Breadcrumb::TYPE_HTTP,
                'http',
                "GET /shift/calendar/abo/$token",
                ['url' => "https://artwork.test/reset-password?token=$token", 'http.query' => "token=$token"]
            ),
        ]);
        $span = new Span();
        $span->setDescription("GET https://artwork.test/external/login/$token");
        $span->setData(['url' => "/setup?token=$token"]);
        $event->setSpans([$span]);

        $scrubbed = SentryEventScrubber::beforeSend($event);

        $this->assertNotNull($scrubbed);
        $breadcrumb = $scrubbed->getBreadcrumbs()[0];
        $dump = json_encode([
            $scrubbed->getRequest(),
            $scrubbed->getTransaction(),
            $scrubbed->getTags(),
            $scrubbed->getExtra(),
            $scrubbed->getContexts(),
            $breadcrumb->getMessage(),
            $breadcrumb->getMetadata(),
            $scrubbed->getSpans()[0]->getDescription(),
            $scrubbed->getSpans()[0]->getData(),
        ]);
        $this->assertStringNotContainsString($token, (string) $dump);

        $this->assertSame(
            'https://artwork.test/users/invitations/accept?token=[Filtered]',
            $scrubbed->getRequest()['url']
        );
        $this->assertSame('token=[Filtered]', $scrubbed->getRequest()['query_string']);
        $this->assertSame(['artwork.test'], $scrubbed->getRequest()['headers']['host']);
        $this->assertSame('/external/login/[Filtered]', $scrubbed->getTransaction());
        $this->assertSame('http.server', $scrubbed->getContexts()['trace']['op']);
        $this->assertSame('GET /shift/calendar/abo/[Filtered]', $breadcrumb->getMessage());
    }

    #[Test]
    public function transactions_are_scrubbed_through_before_send_transaction(): void
    {
        $config = require __DIR__ . '/../../../config/sentry.php';
        $this->assertSame(
            [SentryEventScrubber::class, 'beforeSendTransaction'],
            $config['before_send_transaction']
        );
        $this->assertTrue(is_callable($config['before_send_transaction']));

        $token = str_repeat('Qq7', 21) . 'w';
        $transaction = Event::createTransaction();
        $transaction->setTransaction("/external/login/$token");
        $transaction->setRequest(['url' => "https://artwork.test/external/login/$token"]);

        $scrubbed = SentryEventScrubber::beforeSendTransaction($transaction);

        $this->assertSame('/external/login/[Filtered]', $scrubbed?->getTransaction());
        $this->assertSame('https://artwork.test/external/login/[Filtered]', $scrubbed?->getRequest()['url']);
    }

    #[Test]
    public function tokens_are_removed_from_exception_texts_and_messages(): void
    {
        $token = str_repeat('Ab1', 21) . 'x';
        $event = Event::createEvent();
        $event->setExceptions([
            new ExceptionDataBag(new \RuntimeException("GET https://artwork.test/external/login/$token failed")),
        ]);
        $event->setMessage("Aufruf von /setup?token=$token", [], "Aufruf von /setup?token=$token");

        $scrubbed = SentryEventScrubber::beforeSend($event);

        $this->assertNotNull($scrubbed);
        $this->assertStringNotContainsString($token, $scrubbed->getExceptions()[0]->getValue());
        $this->assertStringContainsString('/external/login/[Filtered]', $scrubbed->getExceptions()[0]->getValue());
        $this->assertStringNotContainsString($token, (string) $scrubbed->getMessage());
        $this->assertStringNotContainsString($token, (string) $scrubbed->getMessageFormatted());
    }
}
