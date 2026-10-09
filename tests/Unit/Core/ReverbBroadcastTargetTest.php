<?php

namespace Tests\Unit\Core;

use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Broadcasting\BroadcastManager;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Ziel der server-seitigen Broadcasts (config/broadcasting.php): mit REVERB_INTERNAL_HOST direkt an den
 * Reverb-Container, ohne wie bisher über die öffentliche Adresse. Der Browser behält REVERB_HOST.
 */
final class ReverbBroadcastTargetTest extends TestCase
{
    private const array ENV_KEYS = [
        'REVERB_HOST',
        'REVERB_PORT',
        'REVERB_SCHEME',
        'REVERB_INTERNAL_HOST',
        'REVERB_INTERNAL_PORT',
        'REVERB_INTERNAL_SCHEME',
    ];

    /** @var array<string, array{server: mixed, env: mixed, getenv: string|false}> */
    private array $originalEnvironment = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::ENV_KEYS as $key) {
            $this->originalEnvironment[$key] = [
                'server' => $_SERVER[$key] ?? null,
                'env' => $_ENV[$key] ?? null,
                'getenv' => getenv($key),
            ];
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->originalEnvironment as $key => $original) {
            $this->setEnvironmentValue($key, null);
            if ($original['server'] !== null) {
                $_SERVER[$key] = $original['server'];
            }
            if ($original['env'] !== null) {
                $_ENV[$key] = $original['env'];
            }
            if ($original['getenv'] !== false) {
                putenv($key . '=' . $original['getenv']);
            }
        }

        parent::tearDown();
    }

    #[Test]
    public function without_internal_host_the_server_sends_to_the_public_address(): void
    {
        $options = $this->reverbOptionsFor([
            'REVERB_HOST' => 'artwork.example.org',
            'REVERB_PORT' => '443',
            'REVERB_SCHEME' => 'https',
        ]);

        self::assertSame('artwork.example.org', $options['host']);
        self::assertSame('443', $options['port']);
        self::assertSame('https', $options['scheme']);
        self::assertTrue($options['useTLS']);
    }

    #[Test]
    public function internal_host_sends_directly_to_the_reverb_container_over_http_on_8080(): void
    {
        $options = $this->reverbOptionsFor([
            'REVERB_HOST' => 'artwork.example.org',
            'REVERB_PORT' => '443',
            'REVERB_SCHEME' => 'https',
            'REVERB_INTERNAL_HOST' => 'artwork_reverb',
        ]);

        self::assertSame('artwork_reverb', $options['host']);
        self::assertSame(8080, $options['port']);
        self::assertSame('http', $options['scheme']);
        self::assertFalse($options['useTLS']);
    }

    #[Test]
    public function internal_port_and_scheme_can_be_overridden(): void
    {
        $options = $this->reverbOptionsFor([
            'REVERB_HOST' => 'artwork.example.org',
            'REVERB_SCHEME' => 'http',
            'REVERB_INTERNAL_HOST' => 'reverb.internal',
            'REVERB_INTERNAL_PORT' => '6001',
            'REVERB_INTERNAL_SCHEME' => 'https',
        ]);

        self::assertSame('reverb.internal', $options['host']);
        self::assertSame('6001', $options['port']);
        self::assertSame('https', $options['scheme']);
        self::assertTrue($options['useTLS']);
    }

    #[Test]
    public function empty_internal_host_keeps_the_public_address(): void
    {
        $options = $this->reverbOptionsFor([
            'REVERB_HOST' => 'artwork.example.org',
            'REVERB_PORT' => '443',
            'REVERB_SCHEME' => 'https',
            'REVERB_INTERNAL_HOST' => '',
            'REVERB_INTERNAL_PORT' => '6001',
        ]);

        self::assertSame('artwork.example.org', $options['host']);
        self::assertSame('443', $options['port']);
        self::assertTrue($options['useTLS']);
    }

    #[Test]
    public function browser_config_keeps_the_public_address_when_internal_host_is_set(): void
    {
        $this->applyEnvironment([
            'REVERB_HOST' => 'artwork.example.org',
            'REVERB_SCHEME' => 'https',
            'REVERB_INTERNAL_HOST' => 'artwork_reverb',
        ]);

        $frontendConfig = require base_path('config/frontend.php');

        self::assertSame('artwork.example.org', $frontendConfig['reverb']['host']);
        self::assertSame('https', $frontendConfig['reverb']['scheme']);
    }

    #[Test]
    public function pusher_client_publishes_to_the_internal_reverb_container(): void
    {
        $this->applyEnvironment([
            'REVERB_HOST' => 'artwork.example.org',
            'REVERB_PORT' => '443',
            'REVERB_SCHEME' => 'https',
            'REVERB_INTERNAL_HOST' => 'artwork_reverb',
        ]);
        $connection = (require base_path('config/broadcasting.php'))['connections']['reverb'];
        $connection['key'] = 'app-key';
        $connection['secret'] = 'app-secret';
        $connection['app_id'] = 'app-id';
        config()->set('broadcasting.connections.reverb', $connection);

        $broadcaster = $this->app->make(BroadcastManager::class)->connection('reverb');

        self::assertInstanceOf(PusherBroadcaster::class, $broadcaster);
        $settings = $broadcaster->getPusher()->getSettings();
        self::assertSame('http', $settings['scheme']);
        self::assertSame('artwork_reverb', $settings['host']);
        self::assertSame(8080, $settings['port']);
    }

    /**
     * @param array<string, string> $environment
     * @return array{host: mixed, port: mixed, scheme: mixed, useTLS: bool, timeout: float}
     */
    private function reverbOptionsFor(array $environment): array
    {
        $this->applyEnvironment($environment);

        return (require base_path('config/broadcasting.php'))['connections']['reverb']['options'];
    }

    /**
     * @param array<string, string> $environment Nicht genannte Schlüssel aus ENV_KEYS werden entfernt.
     */
    private function applyEnvironment(array $environment): void
    {
        foreach (self::ENV_KEYS as $key) {
            $this->setEnvironmentValue($key, $environment[$key] ?? null);
        }
    }

    private function setEnvironmentValue(string $key, ?string $value): void
    {
        if ($value === null) {
            unset($_SERVER[$key], $_ENV[$key]);
            putenv($key);

            return;
        }

        $_SERVER[$key] = $value;
        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }
}
