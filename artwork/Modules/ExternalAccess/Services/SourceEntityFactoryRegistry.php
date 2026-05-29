<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\Crm\Contracts\CrmEntity;
use Artwork\Modules\ExternalAccess\DTOs\InviteExternalCommand;
use Artwork\Modules\ExternalAccess\Exceptions\UnsupportedContactTypeException;

/**
 * Maps a CRM contact-type slug to the source entity (Freelancer, ServiceProvider, …) that
 * must be created when inviting a person of that type. Registration is explicit (no reflection
 * or model magic) so that only deliberately whitelisted contact types are invitable.
 */
final class SourceEntityFactoryRegistry
{
    /** @var array<string, callable(InviteExternalCommand): CrmEntity> */
    private array $factories = [];

    /** @var array<string, list<string>> */
    private array $requiredFields = [];

    /**
     * @param callable(InviteExternalCommand): CrmEntity $factory builds AND persists the entity
     * @param list<string> $requiredPublicFields source-entity columns the inviter must supply
     */
    public function register(string $slug, callable $factory, array $requiredPublicFields = []): void
    {
        $this->factories[$slug] = $factory;
        $this->requiredFields[$slug] = array_values($requiredPublicFields);
    }

    public function isInvitable(string $slug): bool
    {
        return isset($this->factories[$slug]);
    }

    /**
     * @return list<string>
     */
    public function requiredPublicFields(string $slug): array
    {
        return $this->requiredFields[$slug] ?? [];
    }

    public function create(string $slug, InviteExternalCommand $command): CrmEntity
    {
        if (!isset($this->factories[$slug])) {
            throw UnsupportedContactTypeException::forSlug($slug);
        }

        return ($this->factories[$slug])($command);
    }
}
