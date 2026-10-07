<?php

namespace Artwork\Core\Database\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Laravel\Scout\Builder as ScoutBuilder;
use LogicException;
use Meilisearch\Exceptions\ApiException;

/**
 * Scout-Suche mit SQL-Ersatz für Instanzen, auf denen der Meilisearch-Index (noch) nicht existiert.
 */
trait SearchesWithSqlFallback
{
    /**
     * Meilisearch liefert ohne take() höchstens so viele Treffer; der SQL-Ersatz hält sich daran.
     */
    private const MEILISEARCH_DEFAULT_LIMIT = 20;

    /**
     * Führt eine Scout-Suche aus. Existiert der Meilisearch-Index noch nicht (auf der Instanz wurde
     * nie ein Datensatz dieses Typs indexiert), wird stattdessen per SQL-LIKE über die angegebenen
     * Spalten gesucht; jedes Wort des Suchbegriffs muss in mindestens einer Spalte vorkommen.
     * Andere Meilisearch-Fehler werden weitergereicht.
     *
     * Der Ersatz übernimmt das Limit des Scout-Builders (take(), sonst 20 wie Meilisearch) und einen
     * per query() gesetzten Callback. Index-Filter (where/whereIn/whereNotIn), Sortierungen und ein
     * eigener Such-Callback lassen sich nicht auf SQL abbilden: dann wird eine LogicException
     * geworfen, statt sie still zu ignorieren.
     *
     * @param array<int, string> $fallbackColumns
     *
     * @throws ApiException bei anderen Meilisearch-Fehlern als index_not_found
     * @throws LogicException wenn der Scout-Builder nicht abbildbare Bedingungen enthält
     */
    protected function getScoutResultsOrSqlFallback(
        ScoutBuilder $scoutBuilder,
        string $search,
        array $fallbackColumns
    ): EloquentCollection {
        try {
            return $scoutBuilder->get();
        } catch (ApiException $exception) {
            if ($exception->errorCode !== 'index_not_found') {
                throw $exception;
            }
        }

        $this->assertScoutBuilderIsSqlCompatible($scoutBuilder);

        $query = $scoutBuilder->model->newQuery();
        $terms = preg_split('/\s+/', trim($search), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($terms as $term) {
            $pattern = '%' . addcslashes($term, '%_\\') . '%';
            $query->where(function (Builder $builder) use ($fallbackColumns, $pattern): void {
                foreach ($fallbackColumns as $column) {
                    $builder->orWhere($column, 'like', $pattern);
                }
            });
        }

        if ($scoutBuilder->queryCallback !== null) {
            call_user_func($scoutBuilder->queryCallback, $query);
        }

        return $query->limit($scoutBuilder->limit ?? self::MEILISEARCH_DEFAULT_LIMIT)->get();
    }

    private function assertScoutBuilderIsSqlCompatible(ScoutBuilder $scoutBuilder): void
    {
        // __soft_deleted = 0 setzt Scout selbst (scout.soft_delete) — entspricht dem SoftDeletes-Scope von newQuery()
        $wheres = $scoutBuilder->wheres;
        if (($wheres['__soft_deleted'] ?? null) === 0) {
            unset($wheres['__soft_deleted']);
        }

        $unsupported = array_keys(array_filter([
            'where' => $wheres !== [],
            'whereIn' => $scoutBuilder->whereIns !== [],
            'whereNotIn' => $scoutBuilder->whereNotIns !== [],
            'orderBy' => $scoutBuilder->orders !== [],
            'callback' => $scoutBuilder->callback !== null,
        ]));

        if ($unsupported !== []) {
            throw new LogicException(sprintf(
                'SQL-Ersatz für die Scout-Suche auf %s kann %s nicht abbilden.',
                $scoutBuilder->model::class,
                implode(', ', $unsupported)
            ));
        }
    }
}
