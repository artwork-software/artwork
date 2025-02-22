<?php

namespace Artwork\Modules\Sage100\Clients;

class NullSageClient implements SageClient
{
    public function getData(array $query = []): array
    {
        return [];
    }
}
