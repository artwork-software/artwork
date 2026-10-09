<?php

namespace Tests\Concerns;

use Artwork\Modules\User\Models\User;
use GuzzleHttp\Psr7\Response;
use Laravel\Scout\Builder;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\NullEngine;
use Meilisearch\Exceptions\ApiException;

trait FakesMeilisearchErrors
{
    /**
     * Registriert eine Scout-Engine, die für alle Modelle außer User wie Meilisearch mit dem
     * angegebenen Fehlercode antwortet (der users-Index existiert auf jeder Instanz).
     */
    protected function useMeilisearchEngineFailingWith(string $errorCode): void
    {
        app(EngineManager::class)->extend('failing-meilisearch', fn() => new class ($errorCode) extends NullEngine {
            public function __construct(private readonly string $errorCode)
            {
            }

            public function search(Builder $builder): mixed
            {
                if ($builder->model instanceof User) {
                    return parent::search($builder);
                }

                throw new ApiException(new Response(404), [
                    'message' => 'Index `' . $builder->model->searchableAs() . '` not found.',
                    'code' => $this->errorCode,
                    'type' => 'invalid_request',
                    'link' => 'https://docs.meilisearch.com/errors#' . $this->errorCode,
                ]);
            }
        });

        config(['scout.driver' => 'failing-meilisearch']);
        app(EngineManager::class)->forgetDrivers();
    }
}
