<?php

namespace Tests\Unit\App\Exports;

use Artwork\Modules\Project\Exports\BudgetsByBudgetDeadlineExport;
use Illuminate\Support\Facades\View;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\TestCase;

class BudgetsByBudgetDeadlineExportTest extends TestCase
{
    public function testView(): void
    {
        $viewMock = \Mockery::mock(\Illuminate\Contracts\View\View::class);

        View::shouldReceive('make')
            ->once()
            ->withArgs(function ($view, $data, $mergeData) {
                return $view === 'exports.projectBudgetsByBudgetDeadline'
                    && is_array($data)
                    && array_key_exists('rows', $data)
                    && is_array($data['rows'])
                    && is_array($mergeData);
            })
            ->andReturn($viewMock);  // Return the mock instead of the View facade itself

        $export = new BudgetsByBudgetDeadlineExport('2022-01-01', '2022-12-31');
        $view = $export->view();

        $this->assertInstanceOf(\Illuminate\Contracts\View\View::class, $view);
    }

    public function testStyles(): void
    {
        $export = new BudgetsByBudgetDeadlineExport('2022-01-01', '2022-12-31');
        $styles = $export->styles(new Worksheet());

        $this->assertIsArray($styles);
        $this->assertArrayHasKey(1, $styles);
        $this->assertArrayHasKey('font', $styles[1]);
        $this->assertArrayHasKey('bold', $styles[1]['font']);
        $this->assertTrue($styles[1]['font']['bold']);
    }
}
