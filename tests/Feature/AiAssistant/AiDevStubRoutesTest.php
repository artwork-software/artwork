<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Http\Controllers\DevStubController;
use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

/**
 * core-ki-bar-ui: Der Entwicklungs-Stub darf ausschliesslich in der Umgebung `local` existieren
 * (Brief „Fallstricke", Konzept C4 §9).
 *
 * Die Umgebung steht fest, sobald die Anwendung gebootet ist — deshalb baut der Test fuer jede
 * Umgebung eine eigene Anwendung (gleiches Vorgehen wie Tests\CreatesApplication) und schaut nur
 * in deren Router. Die Anwendung dieses Testfalls bleibt unberuehrt, damit die
 * DatabaseTransactions-Klammer haelt.
 */
final class AiDevStubRoutesTest extends TestCase
{
    private const ROUTE_NAMES = ['ai.dev-stub.chat', 'ai.dev-stub.confirm'];

    #[Test]
    public function the_stub_routes_exist_in_the_local_environment(): void
    {
        $router = $this->applicationFor('local')->make('router');

        foreach (self::ROUTE_NAMES as $name) {
            $this->assertTrue($router->has($name), "Route {$name} fehlt in der Umgebung local");
        }
    }

    #[Test]
    #[DataProvider('nonLocalEnvironments')]
    public function the_stub_routes_do_not_exist_outside_local(string $environment): void
    {
        $router = $this->applicationFor($environment)->make('router');

        foreach (self::ROUTE_NAMES as $name) {
            $this->assertFalse($router->has($name), "Route {$name} darf in {$environment} nicht existieren");
        }
    }

    /** @return array<string, array{0: string}> */
    public static function nonLocalEnvironments(): array
    {
        return [
            'production' => ['production'],
            'testing' => ['testing'],
        ];
    }

    /** Die laufende Testumgebung ist `testing` — dort darf der Stub auch nicht erreichbar sein. */
    #[Test]
    public function the_stub_is_not_routable_in_the_running_test_environment(): void
    {
        $this->assertSame('testing', $this->app->environment());
        $this->postJson('/ai/dev-stub/chat', [])->assertNotFound();
    }

    #[Test]
    public function the_stub_answers_401_when_the_authorization_header_is_missing(): void
    {
        $response = app(DevStubController::class)->chat($this->chatRequest(withHeaders: false));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(['error' => 'unauthorized'], json_decode($response->getContent(), true));
    }

    #[Test]
    public function the_stub_answers_401_when_the_instance_header_is_missing(): void
    {
        $request = $this->chatRequest();
        $request->headers->remove('X-Artwork-Instance');

        $response = app(DevStubController::class)->chat($request);

        $this->assertSame(401, $response->getStatusCode());
    }

    #[Test]
    public function the_stub_streams_with_the_headers_the_protocol_requires(): void
    {
        $response = app(DevStubController::class)->chat($this->chatRequest());

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame('text/event-stream', $response->headers->get('Content-Type'));
        $this->assertSame('no', $response->headers->get('X-Accel-Buffering'));
        $this->assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));
    }

    #[Test]
    public function the_stub_rejects_a_body_that_does_not_follow_the_request_contract(): void
    {
        $response = app(DevStubController::class)->chat(
            $this->chatRequest(body: ['message' => 'ok', 'client_turn_id' => 'keine-uuid'])
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    /** @param array<string, mixed>|null $body */
    private function chatRequest(bool $withHeaders = true, ?array $body = null): Request
    {
        $request = Request::create('/ai/dev-stub/chat', 'POST', $body ?? [
            'conversation_id' => null,
            'message' => 'ok Hallo',
            'client_turn_id' => (string) Str::uuid(),
        ]);

        if ($withHeaders) {
            $request->headers->set('Authorization', 'Bearer stub-token');
            $request->headers->set('X-Artwork-Instance', 'artwork.ddev.site');
        }

        return $request;
    }

    /** Eigene Anwendungsinstanz mit fest gesetzter Umgebung; die Umgebungsvariablen werden danach
     *  wieder auf den Stand des Testlaufs gebracht. */
    private function applicationFor(string $environment): Application
    {
        $previous = $_ENV['APP_ENV'] ?? 'testing';

        putenv("APP_ENV={$environment}");
        $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = $environment;

        try {
            /** @var Application $app */
            $app = require base_path('bootstrap/app.php');
            $app->make(Kernel::class)->bootstrap();

            $this->assertSame($environment, $app->environment());

            return $app;
        } finally {
            putenv("APP_ENV={$previous}");
            $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = $previous;

            // Das Bootstrapping der zweiten Anwendung setzt Container und Facades global um —
            // ohne diese Zeilen liefe der Rest des Testlaufs gegen die falsche Instanz.
            Container::setInstance($this->app);
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication($this->app);
        }
    }
}
