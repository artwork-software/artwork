<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\MaterialSet\Models\MaterialSet;
use Artwork\Modules\Project\Models\Project;

class ProjectTabMaterialIssueService
{
    public function buildMaterialIssuePayload(Project $project): array
    {
        // Zeitfenster über alle Ausgaben dieses Projekts: Historie außerhalb davon kann
        // period_usage nie beeinflussen — ohne Begrenzung würde pro Artikelzeile die
        // komplette (mehrjährige) Ausgabe-Historie hydratisiert.
        $window = InternalIssue::where('project_id', $project->id)
            ->selectRaw('MIN(start_date) as min_start, MAX(COALESCE(end_date, start_date)) as max_end')
            ->first();
        $minStart = $window?->getAttribute('min_start');
        $maxEnd = $window?->getAttribute('max_end');

        $materials = InternalIssue::where('project_id', $project->id)
            ->with([
                'project',
                'room',
                'articles.images',
                // 🔹 Tags der Artikel
                'articles.tags',
                // falls du auch Berechtigungen an den Tags brauchst:
                'articles.tags.allowedUsers',
                'articles.tags.allowedDepartments',
                'articles.statusValues',
                'articles.detailedArticleQuantities.status',
                // Für period_usage vorladen — sonst feuert getAvailableStock
                // zwei Overlap-Queries pro Artikel und Ausgabe (N+1).
                'articles.internalIssues' => function ($query) use ($minStart, $maxEnd): void {
                    if ($minStart === null) {
                        $query->whereRaw('1 = 0');
                        return;
                    }
                    $query->where('internal_issues.start_date', '<=', $maxEnd)
                        ->where(function ($subQuery) use ($minStart): void {
                            $subQuery->where('internal_issues.end_date', '>=', $minStart)
                                ->orWhereNull('internal_issues.end_date');
                        });
                },
                'articles.externalIssues' => function ($query) use ($minStart, $maxEnd): void {
                    if ($minStart === null) {
                        $query->whereRaw('1 = 0');
                        return;
                    }
                    $query->where('external_issues.issue_date', '<=', $maxEnd)
                        ->where(function ($subQuery) use ($minStart): void {
                            $subQuery->where('external_issues.return_date', '>=', $minStart)
                                ->orWhereNull('external_issues.return_date');
                        });
                },
                'specialItems',
                'files',
                'responsibleUsers',
            ])
            ->get();

        // Globale Zeitraum-Auslastung pro Artikel (alle internen + externen Ausgaben,
        // inkl. dieser Ausgabe) für den Balken/Überbuchungs-Badge im Projekt-Tab.
        foreach ($materials as $issue) {
            $startDate = $issue->start_date?->toDateString();
            $endDate = $issue->end_date?->toDateString() ?? $startDate;

            foreach ($issue->articles as $article) {
                if ($startDate === null) {
                    $article->setAttribute('period_usage', null);
                    continue;
                }

                // getAvailableStock nutzt geladene Relationen ungefiltert —
                // deshalb hier auf den Zeitraum dieser Ausgabe einschränken.
                $article->setRelation('internalIssues', $article->internalIssues->filter(
                    fn ($otherIssue) => ($otherIssue->start_date === null
                            || $otherIssue->start_date->toDateString() <= $endDate)
                        && ($otherIssue->end_date === null
                            || $otherIssue->end_date->toDateString() >= $startDate)
                )->values());
                $article->setRelation('externalIssues', $article->externalIssues->filter(
                    fn ($otherIssue) => ($otherIssue->issue_date === null
                            || $otherIssue->issue_date->toDateString() <= $endDate)
                        && ($otherIssue->return_date === null
                            || $otherIssue->return_date->toDateString() >= $startDate)
                )->values());

                $periodUsage = $article->getAvailableStock($startDate, $endDate);

                // getAvailableStock zählt nur "Einsatzbereit"-Statusmengen. Artikel ohne
                // gepflegte Statusmengen hätten total=0 und würden im Tab fälschlich als
                // 100% überbucht (rot) erscheinen — dann auf die Gesamtmenge zurückfallen.
                $hasMaintainedReadyStatus = $article->is_detailed_quantity
                    ? $article->detailedArticleQuantities->isNotEmpty()
                    : $article->statusValues->firstWhere('name', 'Einsatzbereit') !== null;
                if (!$hasMaintainedReadyStatus && $periodUsage['total'] <= 0 && (float) $article->quantity > 0) {
                    $periodUsage['total'] = (float) $article->quantity;
                    $periodUsage['quantity'] = (float) $article->quantity;
                    $periodUsage['available'] = max($periodUsage['total'] - $periodUsage['reserved'], 0);
                }

                $article->setAttribute('period_usage', $periodUsage);

                // Nicht mit ins Inertia-Payload serialisieren.
                $article->unsetRelation('internalIssues');
                $article->unsetRelation('externalIssues');
            }
        }

        $externalMaterials = ExternalIssue::where('project_id', $project->id)
            ->with([
                'project',
                'articles.images',
                'articles.tags',
                'articles.tags.allowedUsers',
                'articles.tags.allowedDepartments',
                'specialItems',
                'files',
                'issuedBy',
                'receivedBy',
            ])
            ->get();

        return [
            'materials' => $materials,
            'externalMaterials' => $externalMaterials,
            'first_event' => $project->events()->orderBy('start_time', 'ASC')->first(),
            'last_event' => $project->events()->orderBy('end_time', 'DESC')->first(),
            'materialSets' => MaterialSet::with('items.article', 'items.article.category', 'items.article.subCategory')->get(),
        ];
    }
}

