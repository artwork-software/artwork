<?php

namespace Artwork\Modules\Change\Services;

use Antonrom\ModelChangesHistory\Models\Change;
use Artwork\Modules\Change\Builders\ChangeBuilder;
use Artwork\Modules\Change\Interfaces\Builder;
use Artwork\Modules\Change\Repositories\ChangeRepository;
use InvalidArgumentException;

readonly class ChangeService
{
    public function __construct(private ChangeRepository $changeRepository)
    {
    }

    public function createBuilder(): ChangeBuilder
    {
        return ChangeBuilder::newInstance();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function saveFromBuilder(Builder $builder): Change
    {
        return $this->changeRepository->save($builder->build());
    }
}
