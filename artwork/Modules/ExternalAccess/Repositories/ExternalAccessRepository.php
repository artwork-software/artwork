<?php

namespace Artwork\Modules\ExternalAccess\Repositories;

use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;

class ExternalAccessRepository extends BaseRepository
{
    public function __construct(private readonly ExternalAccess $externalAccess)
    {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification
    {
        return $this->externalAccess->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->externalAccess->newModelQuery();
    }

    public function findByEmail(string $email): ?ExternalAccess
    {
        /** @var ExternalAccess|null $external */
        $external = $this->getNewModelQuery()->where('email', $email)->first();

        return $external;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): ExternalAccess
    {
        /** @var ExternalAccess $external */
        $external = $this->getNewModelInstance();
        $external->fill($attributes);
        $this->save($external);

        return $external;
    }
}
