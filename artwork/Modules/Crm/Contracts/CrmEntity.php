<?php

namespace Artwork\Modules\Crm\Contracts;

interface CrmEntity
{
    public function getCrmFields(): array;

    public function getCrmDisplayName(): string;

    public function getCrmContactTypeSlug(): string;

    public function resolveCrmFieldValue(string $propertyName): ?string;

    public function setCrmFieldValue(string $propertyName, ?string $value): bool;
}
