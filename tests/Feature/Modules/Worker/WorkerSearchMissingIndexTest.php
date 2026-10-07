<?php

namespace Tests\Feature\Modules\Worker;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Freelancer\Repositories\FreelancerRepository;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\ServiceProvider\Repositories\ServiceProviderRepository;
use Artwork\Modules\User\Models\User;
use GuzzleHttp\Psr7\Response;
use Laravel\Scout\Builder;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\NullEngine;
use Meilisearch\Exceptions\ApiException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Sentry: "Index `service_providers` not found" — auf Instanzen, auf denen nie ein Dienstleister
 * (bzw. Freelancer) indexiert wurde, existiert der Meilisearch-Index nicht und die Personensuche
 * warf eine 500. Die Repositories fallen dann auf eine SQL-Suche zurück.
 */
final class WorkerSearchMissingIndexTest extends FeatureTestCase
{
    #[Test]
    public function service_provider_search_falls_back_to_sql_when_index_is_missing(): void
    {
        $this->useMeilisearchEngineFailingWith('index_not_found');
        $matching = ServiceProvider::factory()->create(['provider_name' => 'Lichttechnik Müller GmbH']);
        ServiceProvider::factory()->create(['provider_name' => 'Tonstudio Schmidt']);

        $results = app(ServiceProviderRepository::class)->scoutSearch('lichttechnik müller');

        $this->assertSame([$matching->id], $results->pluck('id')->all());
        $this->assertSame(ServiceProvider::class, $results->first()['manager_type']);
    }

    #[Test]
    public function freelancer_search_falls_back_to_sql_and_matches_full_name(): void
    {
        $this->useMeilisearchEngineFailingWith('index_not_found');
        $matching = Freelancer::factory()->create(['first_name' => 'Erika', 'last_name' => 'Mustermann']);
        Freelancer::factory()->create(['first_name' => 'Erika', 'last_name' => 'Beispiel']);

        $results = app(FreelancerRepository::class)->scoutSearch('Erika Muster');

        $this->assertSame([$matching->id], $results->pluck('id')->all());
    }

    #[Test]
    public function sql_fallback_treats_like_wildcards_literally(): void
    {
        $this->useMeilisearchEngineFailingWith('index_not_found');
        ServiceProvider::factory()->create(['provider_name' => 'Bühnenbau']);

        $this->assertTrue(app(ServiceProviderRepository::class)->scoutSearch('%')->isEmpty());
    }

    #[Test]
    public function other_meilisearch_errors_are_not_swallowed(): void
    {
        $this->useMeilisearchEngineFailingWith('invalid_api_key');

        $this->expectException(ApiException::class);

        app(ServiceProviderRepository::class)->scoutSearch('Lichttechnik');
    }

    #[Test]
    public function worker_search_endpoint_returns_results_when_index_is_missing(): void
    {
        $this->actingAsAdmin();
        $this->useMeilisearchEngineFailingWith('index_not_found');
        $serviceProvider = ServiceProvider::factory()->create(['provider_name' => 'Lichttechnik Müller GmbH']);

        $response = $this->postJson(route('worker.scoutSearch'), ['query' => 'Lichttechnik']);

        $response->assertOk();
        $this->assertContains($serviceProvider->id, collect($response->json())->pluck('id')->all());
    }

    /**
     * Registriert eine Scout-Engine, die für alle Modelle außer User wie Meilisearch mit dem
     * angegebenen Fehlercode antwortet (der users-Index existiert auf jeder Instanz).
     */
    private function useMeilisearchEngineFailingWith(string $errorCode): void
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
