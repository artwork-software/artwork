<?php

namespace Tests\Unit\Modules\Project\Exports;

use Artwork\Modules\Project\Exports\ProjectRoleMatrixExcelExport;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProjectRoleMatrixExcelExportTest extends TestCase
{
    private function makeExport(array $matrix): ProjectRoleMatrixExcelExport
    {
        return new ProjectRoleMatrixExcelExport(
            $matrix,
            Carbon::createFromFormat('Y-m-d', '2026-06-01'),
            Carbon::createFromFormat('Y-m-d', '2026-06-30'),
            'de',
        );
    }

    #[Test]
    public function itRendersProjectsAsColumnsAndPersonsAsRows(): void
    {
        $html = $this->makeExport([
            'projects' => collect([
                ['id' => 1, 'name' => 'Produktion A', 'period_label' => '05.06.2026 – 12.06.2026'],
                ['id' => 2, 'name' => 'Produktion B', 'period_label' => '20.06.2026 – 22.06.2026'],
            ]),
            'rows' => collect([
                ['name' => 'Anna Muster', 'roles' => [1 => 'Regie', 2 => 'Assistenz, Bühne']],
            ]),
        ])->view()->render();

        self::assertStringContainsString('Produktion A', $html);
        self::assertStringContainsString('Produktion B', $html);
        self::assertStringContainsString('Anna Muster', $html);
        self::assertStringContainsString('Regie', $html);
        self::assertStringContainsString('Assistenz, Bühne', $html);
        self::assertStringContainsString('05.06.2026 – 12.06.2026', $html);
    }

    #[Test]
    public function itRendersAnEmptyMatrixWithoutErrors(): void
    {
        $html = $this->makeExport([
            'projects' => collect(),
            'rows' => collect(),
        ])->view()->render();

        self::assertStringContainsString('01.06.2026 – 30.06.2026', $html);
    }
}
