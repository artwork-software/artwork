<?php

namespace Artwork\Modules\AiAssistant\Passport;

use Laravel\Passport\PersonalAccessTokenResult;
use League\OAuth2\Server\AuthorizationServer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * Spiegel von Laravel\Passport\PersonalAccessTokenFactory fuer den ai_session-Grant
 * (core-session-token).
 *
 * Der hier injizierte AuthorizationServer ist NICHT der Singleton, sondern das eigene Server-Objekt
 * aus der kontextuellen Bindung in AiAssistantServiceProvider. Auf ihm ist nur AiSessionGrant
 * aktiviert, mitsamt der 15-Minuten-TTL — die TTL haengt am Grant, nicht an dieser Klasse.
 */
class AiSessionTokenFactory
{
    public function __construct(protected AuthorizationServer $server)
    {
    }

    /**
     * @param string[] $scopes
     */
    public function make(string|int $userId, string $name, array $scopes): PersonalAccessTokenResult
    {
        return new PersonalAccessTokenResult(
            $this->dispatchRequestToAuthorizationServer(
                $this->createRequest($userId, $name, $scopes)
            )
        );
    }

    /**
     * @param string[] $scopes
     */
    protected function createRequest(string|int $userId, string $name, array $scopes): ServerRequestInterface
    {
        return (new PsrHttpFactory())->createRequest(Request::create(config('app.url'), 'POST', [
            'grant_type' => 'ai_session',
            'user_id' => $userId,
            'scope' => implode(' ', $scopes),
            'name' => $name,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function dispatchRequestToAuthorizationServer(ServerRequestInterface $request): array
    {
        return json_decode($this->server->respondToAccessTokenRequest(
            $request,
            app(ResponseInterface::class)
        )->getBody()->__toString(), true);
    }
}
