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
                $article->setAttribute(
                    'period_usage',
                    $startDate ? $article->getAvailableStock($startDate, $endDate) : null
                );
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

