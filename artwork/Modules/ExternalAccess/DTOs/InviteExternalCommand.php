<?php

namespace Artwork\Modules\ExternalAccess\DTOs;

use Artwork\Modules\ExternalAccess\Enums\InviteSource;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final class InviteExternalCommand
{
    /**
     * @param list<TabScopeInput> $tabScopes
     * @param array<string, string> $confidentialFieldValues propertyId => value (filled by the inviter)
     * @param array<string, string> $publicFieldValues source-entity column => value (e.g. first_name, name)
     */
    public function __construct(
        public readonly string $email,
        public readonly int $crmContactTypeId,
        public readonly InviteSource $source,
        public readonly ?int $sourceReferenceProjectId,
        public readonly User $invitedBy,
        public readonly ?CarbonImmutable $crmAccessExpiresAt,
        public readonly array $tabScopes = [],
        public readonly array $confidentialFieldValues = [],
        public readonly array $publicFieldValues = [],
    ) {
    }

    public function normalizedEmail(): string
    {
        return Str::lower(trim($this->email));
    }
}
