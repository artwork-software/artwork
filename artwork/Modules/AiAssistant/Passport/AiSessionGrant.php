<?php

namespace Artwork\Modules\AiAssistant\Passport;

use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use DateInterval;
use Laravel\Passport\Bridge\Client;
use Laravel\Passport\Passport;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\RequestAccessTokenEvent;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Grant fuer KI-Session-Tokens (core-session-token; Konzept 0.2 §1).
 *
 * Spiegel von Laravel\Passport\Bridge\PersonalAccessGrant: nimmt User-ID und Scopes, gibt ein
 * Access-Token aus, kein Refresh-Token. Wie dort wird kein Client-Secret geprueft.
 *
 * WICHTIG: Genau deshalb darf dieser Grant niemals am Singleton-AuthorizationServer aktiviert
 * werden — der bedient das oeffentliche POST /oauth/token, und der Grant-Treffer laeuft allein
 * ueber den Request-Parameter grant_type. Er haengt ausschliesslich an dem eigenen Server, den
 * AiAssistantServiceProvider kontextuell an AiSessionTokenFactory bindet.
 *
 * Der Client wird ueber die von ai:install abgelegte ID gesucht, nicht ueber grant_types —
 * schema-unabhaengig und dieselbe ID, die artwork-ai fuers Tenant-Onboarding bekommt.
 */
class AiSessionGrant extends AbstractGrant
{
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL
    ): ResponseTypeInterface {
        if (!$userIdentifier = $this->getRequestParameter('user_id', $request)) {
            throw OAuthServerException::invalidRequest('user_id');
        }

        $client = $this->aiSessionClient();

        $scopes = $this->scopeRepository->finalizeScopes(
            $this->validateScopes($this->getRequestParameter('scope', $request, $this->defaultScope)),
            $this->getIdentifier(),
            $client,
            $userIdentifier
        );

        $accessToken = $this->issueAccessToken($accessTokenTTL, $client, $userIdentifier, $scopes);

        $this->getEmitter()->emit(
            new RequestAccessTokenEvent(RequestEvent::ACCESS_TOKEN_ISSUED, $request, $accessToken)
        );

        Passport::token()->newQuery()->whereKey($accessToken->getIdentifier())->update([
            'name' => $this->getRequestParameter('name', $request),
        ]);

        $responseType->setAccessToken($accessToken);

        return $responseType;
    }

    private function aiSessionClient(): ClientEntityInterface
    {
        $clientId = app(AiAssistantSettings::class)->ai_session_client_id;

        if ($clientId === null) {
            throw new RuntimeException(
                'Kein ai-session-Client hinterlegt. Bitte "php artisan ai:install" ausfuehren.'
            );
        }

        $model = Passport::client()->newQuery()->where('revoked', false)->find($clientId);

        if ($model === null) {
            throw new RuntimeException(
                sprintf('Der hinterlegte ai-session-Client (%s) existiert nicht oder ist widerrufen.', $clientId)
            );
        }

        return new Client(
            $model->getKey(),
            $model->name,
            $model->redirect_uris,
            $model->confidential(),
            $model->provider,
            $model->grant_types
        );
    }

    public function getIdentifier(): string
    {
        return 'ai_session';
    }
}
