<?php

namespace Artwork\Modules\Sage100\Clients;

use Illuminate\Http\Client\PendingRequest;

interface SageClient
{
    public function getData(array $query = []): array;
}
