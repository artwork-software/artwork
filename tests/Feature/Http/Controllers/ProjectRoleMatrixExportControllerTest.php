<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectRoleMatrixExportControllerTest extends FeatureTestCase
{
    /** Matrix über alle Produktionen: globales Leserecht plus Recht für die Exporte der Projektübersicht */
    private const MATRIX_PERMISSIONS = [
        PermissionEnum::PROJECT_VIEW->value,
        PermissionEnum::PROJECT_OVERVIEW_EXPORT->value,
    ];

    #[Test]
    public function itRequiresProjectViewPermission(): void
    {
        // Matrix über alle Produktionen hinweg — Team-Mitgliedschaft allein reicht nicht
        $this->actingAsUserWith([]);

        $this->get(route('projects.export.project-role-matrix', [
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]))->assertForbidden();
    }

    #[Test]
    public function itRequiresTheOverviewExportPermission(): void
    {
        $this->actingAsUserWith(PermissionEnum::PROJECT_VIEW->value);

        $this->get(route('projects.export.project-role-matrix', [
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]))->assertForbidden();
    }

    #[Test]
    public function itValidatesTheDateRange(): void
    {
        $this->actingAsUserWith(self::MATRIX_PERMISSIONS);

        $this->get(route('projects.export.project-role-matrix', [
            'start_date' => '2026-06-30',
            'end_date' => '2026-06-01',
        ]))->assertSessionHasErrors(['end_date']);
    }

    #[Test]
    public function itRejectsRangesOverThirtySixMonths(): void
    {
        $this->actingAsUserWith(self::MATRIX_PERMISSIONS);

        $this->get(route('projects.export.project-role-matrix', [
            'start_date' => '2026-01-01',
            'end_date' => '2029-06-01',
        ]))->assertSessionHasErrors(['end_date']);
    }

    #[Test]
    public function itDownloadsTheProjectRoleMatrixWorkbook(): void
    {
        Excel::fake();
        $this->actingAsUserWith(self::MATRIX_PERMISSIONS);

        $this->get(route('projects.export.project-role-matrix', [
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]))->assertOk();

        Excel::assertDownloaded('project_role_matrix_2026-06-01_2026-06-30.xlsx');
    }
}
