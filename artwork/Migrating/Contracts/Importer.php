<?php

namespace Artwork\Migrating\Contracts;

use Artwork\Migrating\ImportConfig;

interface Importer
{
    public function getConfig(): ImportConfig;

    public function getDataAggregator(): DataAggregator;
}
