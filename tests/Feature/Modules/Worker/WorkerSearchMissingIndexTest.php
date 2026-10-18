<?php

namespace Tests\Feature\Modules\Worker;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Freelancer\Repositories\FreelancerRepository;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\ServiceProvider\Repositories\ServiceProviderRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Laravel\Scout\Builder as ScoutBuilder;
use LogicException;
use Meilisearch\Exceptions\ApiException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\FakesMeilisearchErrors;
use Tests\Feature\FeatureTestCase;

/**
 * Sentry: "Index `service_providers` not found" — auf Instanzen, auf denen nie ein Dienstleister
 * (bzw. Freelancer) indexiert wurde, existiert der Meilisearch-Index nicht und die Personensuche
 * warf eine 500. Die Repositories fallen dann auf eine SQL-Suche zurück.
 */
final class WorkerSearchMissingIndexTest extends FeatureTestCase
{
    use FakesMeilisearchErrors;

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

    #[Test]
    public function sql_fallback_returns_at_most_twenty_hits_like_meilisearch(): void
    {
        $this->useMeilisearchEngineFailingWith('index_not_found');
        ServiceProvider::factory()->count(25)->sequence(
            fn($sequence) => ['provider_name' => 'Lichttechnik ' . $sequence->index]
        )->create();

        $this->assertCount(20, app(ServiceProviderRepository::class)->scoutSearch('Lichttechnik'));
    }

    #[Test]
    public function sql_fallback_respects_the_limit_and_query_callback_of_the_scout_builder(): void
    {
        $this->useMeilisearchEngineFailingWith('index_not_found');
        $providers = ServiceProvider::factory()->count(4)->sequence(
            fn($sequence) => ['provider_name' => 'Tontechnik ' . $sequence->index]
        )->create();
        $excluded = $providers->first();

        $results = $this->fallbackSearch(
            ServiceProvider::search('Tontechnik')
                ->take(2)
                ->query(fn(Builder $query) => $query->whereKeyNot($excluded->id)),
            'Tontechnik'
        );

        $this->assertCount(2, $results);
        $this->assertNotContains($excluded->id, $results->pluck('id')->all());
    }

    #[Test]
    public function sql_fallback_refuses_index_filters_instead_of_ignoring_them(): void
    {
        $this->useMeilisearchEngineFailingWith('index_not_found');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('where');

        $this->fallbackSearch(ServiceProvider::search('Lichttechnik')->where('type', 'extern'), 'Lichttechnik');
    }

    /**
     * Ruft den geschützten Fallback über ein minimales Repository auf.
     *
     * @return EloquentCollection<int, ServiceProvider>
     */
    private function fallbackSearch(ScoutBuilder $scoutBuilder, string $search): EloquentCollection
    {
        $repository = new class extends BaseRepository {
            /**
             * @return EloquentCollection<int, ServiceProvider>
             */
            public function search(ScoutBuilder $scoutBuilder, string $search): EloquentCollection
            {
                return $this->getScoutResultsOrSqlFallback($scoutBuilder, $search, ['provider_name']);
            }
        };

        return $repository->search($scoutBuilder, $search);
    }
}
