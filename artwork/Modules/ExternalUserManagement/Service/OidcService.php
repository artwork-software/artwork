<?php

namespace Artwork\Modules\ExternalUserManagement\Service;

use Artwork\Modules\ExternalUserManagement\Api\OidcApi;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Illuminate\Http\RedirectResponse;

class OidcService
{
    public function __construct(
        private readonly OidcApi $oidcApi
    ) {
    }

    public function redirect(ExternalUserSource $source): RedirectResponse
    {
        return $this->oidcApi->redirect($source);
    }

    /**
     * @return array{identifier: string, email: string|null, first_name: string, last_name: string, groups: array<int, string>, email_verified: bool, meta_data: array<string, mixed>}
     */
    public function userFromCallback(ExternalUserSource $source): array
    {
        return $this->oidcApi->userFromCallback($source);
    }

    public function testConnection(ExternalUserSource $source): bool
    {
        return $this->oidcApi->testConnection($source);
    }
}
