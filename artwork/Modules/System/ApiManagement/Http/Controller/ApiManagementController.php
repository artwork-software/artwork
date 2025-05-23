<?php

namespace Artwork\Modules\System\ApiManagement\Http\Controller;

use App\Http\Controllers\Controller;
use Artwork\Core\Api\Models\ApiAccessToken;
use Artwork\Modules\System\ApiManagement\Http\Requests\StoreTokenRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Passport\PersonalAccessTokenResult;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;
use Laravel\Passport\PersonalAccessTokenFactory;

class ApiManagementController extends Controller
{
    public function __construct(
        private readonly TokenRepository $tokenRepository,
        private readonly PersonalAccessTokenFactory $tokenFactory
    ) {}

    public function index(): Response
    {
        $this->authorize('view', Token::class);

        $tokens = Token::with('user', 'apiAccessToken')
            ->where('revoked', false)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (Token $token): array {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'created_at' => $token->created_at,
                    'expires_at' => $token->expires_at,
                    'last_used_at' => $token->last_used_at,
                    'access_token' => $token->apiAccessToken?->access_token ?? null,
                ];
            });

        return Inertia::render('Artwork/Settings/ArtworkApiSettings', [
            'tokens' => $tokens
        ]);
    }

    public function store(StoreTokenRequest $request): RedirectResponse
    {
        $this->authorize('create', Token::class);
        $tokenResult = $this->tokenFactory->make(
            $request->user()->getKey(),
            $request->input('name'),
            []
        );

        if ($expiresAt = $request->input('expires_at')) {
            $tokenResult->token->expires_at = $expiresAt;
            $tokenResult->token->save();
        }

        $apiAccessToken = new ApiAccessToken([
            'passport_token_id' => $tokenResult->token->id,
            'access_token' => $tokenResult->accessToken,
        ]);
        $apiAccessToken->save();

        return back()
            ->with('success', __('API key created successfully.'))
            ->with('plainTextToken', $tokenResult->accessToken);
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('create', Token::class);
        $token = Token::find($id);

        if (!$token) {
            return back()->with('error', __('Token not found.'));
        }

        $this->tokenRepository->revokeAccessToken($id);

        return back()->with('success', __('API key revoked successfully.'));
    }
}
