<?php

namespace Artwork\Modules\ExternalAccess\DTOs;

use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Carbon\CarbonImmutable;

final class TabScopeInput
{
    public function __construct(
        public readonly int $projectTabId,
        public readonly ExternalAccessType $accessType,
        public readonly CarbonImmutable $validFrom,
        public readonly CarbonImmutable $validTo,
    ) {
    }
}
