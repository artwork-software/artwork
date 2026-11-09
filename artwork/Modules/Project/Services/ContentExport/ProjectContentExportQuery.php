<?php

namespace Artwork\Modules\Project\Services\ContentExport;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Projektauswahl des Projektinhalte-Exports. Immer nur Projekte, die die Person öffnen darf; Projektgruppen
 * selbst sind keine Zeilen.
 *
 * - Zeitraum: mindestens ein Termin überschneidet den Zeitraum.
 * - Projektgruppe: Projekt gehört zur Gruppe.
 * - Merkmale (Status, Kategorie, Genre, Bereich, Terminart): mindestens EIN gewähltes Merkmal trifft zu.
 * - Mitglieder: mindestens eine der Personen ist im Projektteam.
 * Die vier Bedingungen sind untereinander UND-verknüpft.
 */
class ProjectContentExportQuery
{
    /**
     * @return Builder<Project>
     */
    public function build(User $user, ProjectContentExportConfiguration $configuration): Builder
    {
        return Project::query()
            ->where('is_group', false)
            ->enterableBy($user)
            ->when(
                $configuration->hasDateRange(),
                fn (Builder $query) => $query->whereHas(
                    'events',
                    fn (Builder $events) => $this->overlapping($events, $configuration)
                )
            )
            ->when(
                $configuration->projectGroupId !== null,
                fn (Builder $query) => $query->whereHas(
                    'groups',
                    fn (Builder $groups) => $groups->whereKey($configuration->projectGroupId)
                )
            )
            ->when(
                $configuration->hasCharacteristics(),
                fn (Builder $query) => $query->where(
                    fn (Builder $any) => $this->anyCharacteristic($any, $configuration)
                )
            )
            ->when(
                $configuration->memberIds !== [],
                fn (Builder $query) => $query->whereHas(
                    'users',
                    fn (Builder $users) => $users->whereKey($configuration->memberIds)
                )
            );
    }

    public function count(User $user, ProjectContentExportConfiguration $configuration): int
    {
        return $this->build($user, $configuration)->count();
    }

    /**
     * Termine, die den Zeitraum überschneiden (Beginn vor Zeitraumende, Ende nach Zeitraumbeginn).
     *
     * @param Builder<\Artwork\Modules\Event\Models\Event> $events
     * @return Builder<\Artwork\Modules\Event\Models\Event>
     */
    public function overlapping(Builder $events, ProjectContentExportConfiguration $configuration): Builder
    {
        if (!$configuration->hasDateRange()) {
            return $events;
        }

        return $events
            ->where('events.start_time', '<=', $configuration->dateEnd)
            ->where('events.end_time', '>=', $configuration->dateStart);
    }

    /**
     * @param Builder<Project> $query
     */
    private function anyCharacteristic(Builder $query, ProjectContentExportConfiguration $configuration): void
    {
        if ($configuration->projectStateIds !== []) {
            $query->orWhereIn('projects.state', $configuration->projectStateIds);
        }
        if ($configuration->categoryIds !== []) {
            $query->orWhereHas(
                'categories',
                fn (Builder $categories) => $categories->whereKey($configuration->categoryIds)
            );
        }
        if ($configuration->genreIds !== []) {
            $query->orWhereHas('genres', fn (Builder $genres) => $genres->whereKey($configuration->genreIds));
        }
        if ($configuration->sectorIds !== []) {
            $query->orWhereHas('sectors', fn (Builder $sectors) => $sectors->whereKey($configuration->sectorIds));
        }
        if ($configuration->eventTypeIds !== []) {
            // Bei gesetztem Zeitraum zählt nur ein Termin dieser Art im Zeitraum
            $query->orWhereHas(
                'events',
                fn (Builder $events) => $this->overlapping($events, $configuration)
                    ->whereIn('events.event_type_id', $configuration->eventTypeIds)
            );
        }
    }
}
