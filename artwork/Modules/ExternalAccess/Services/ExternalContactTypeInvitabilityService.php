<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\Crm\Models\CrmContactType;

/**
 * Decides whether a CRM contact type can be invited for external access and which public
 * fields the inviter must supply.
 *
 * Two paths:
 *  - SYSTEM types (freelancer, service_provider, …) have a registered factory that builds a
 *    dedicated source entity. Their required public fields come from that factory.
 *  - FREELY CREATED types have no factory. They are invited as a standalone CRM contact and
 *    only require a display name.
 */
final class ExternalContactTypeInvitabilityService
{
    /** Public field the inviter must supply for a freely created (generic) contact type. */
    public const GENERIC_DISPLAY_NAME_FIELD = 'display_name';

    public function __construct(
        private readonly SourceEntityFactoryRegistry $factoryRegistry,
    ) {
    }

    /**
     * Any active contact type is invitable: system types via their factory, all others via the
     * generic standalone-contact path.
     */
    public function isInvitable(CrmContactType $type): bool
    {
        if (!$type->is_active) {
            return false;
        }

        // A system type without a registered factory would be a misconfiguration, not invitable.
        if ($type->is_system) {
            return $this->factoryRegistry->isInvitable($type->slug);
        }

        return true;
    }

    /**
     * Generic (freely created) types are created as a standalone CRM contact instead of a
     * dedicated source entity.
     */
    public function usesGenericPath(CrmContactType $type): bool
    {
        return !$this->factoryRegistry->isInvitable($type->slug);
    }

    /**
     * @return list<string> source-entity columns (system) or the display-name field (generic)
     */
    public function requiredPublicFields(CrmContactType $type): array
    {
        if ($this->usesGenericPath($type)) {
            return [self::GENERIC_DISPLAY_NAME_FIELD];
        }

        return $this->factoryRegistry->requiredPublicFields($type->slug);
    }
}
