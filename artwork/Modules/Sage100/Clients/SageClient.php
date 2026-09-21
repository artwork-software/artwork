<?php

namespace Artwork\Modules\Sage100\Clients;

interface SageClient
{
    public function getData(array $query = []): array;

    public function testConnection(): bool;
}
