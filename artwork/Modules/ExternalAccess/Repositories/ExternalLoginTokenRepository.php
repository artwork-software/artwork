<?php

namespace Artwork\Modules\ExternalAccess\Repositories;

use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ExternalAccess\Models\ExternalLoginToken;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;

class ExternalLoginTokenRepository extends BaseRepository
{
    public function __construct(private readonly ExternalLoginToken $externalLoginToken)
    {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification
    {
        return $this->externalLoginToken->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->externalLoginToken->newModelQuery();
    }

    public function findByHashWithLock(string $tokenHash): ?ExternalLoginToken
    {
        /** @var ExternalLoginToken|null $token */
        $token = $this->getNewModelQuery()
            ->where('token_hash', $tokenHash)
            ->lockForUpdate()
            ->first();

        return $token;
    }

    public function pruneExpiredAndUsed(): int
    {
        $threshold = now()->subDay();

        return $this->getNewModelQuery()
            ->where(function (Builder $query) use ($threshold): void {
                $query->where('expires_at', '<', $threshold)
                    ->orWhere(function (Builder $sub) use ($threshold): void {
                        $sub->whereNotNull('used_at')
                            ->where('used_at', '<', $threshold);
                    });
            })
            ->delete();
    }
}
