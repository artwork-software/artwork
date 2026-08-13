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
                'articles.internalIssues',
                'articles.externalIssues',
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

                $article->setAttribute('period_usage', $article->getAvailableStock($startDate, $endDate));

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

