<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkerShiftPlanPdfExportRequest;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Services\WorkerShiftPlanPdfBuilder;
use Artwork\Modules\User\Models\User;
use Barryvdh\Snappy\PdfWrapper;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\ResponseFactory as InertiaResponseFactory;
use Symfony\Component\HttpFoundation\Response;

class WorkerShiftPlanPdfExportController extends Controller
{
    public function __construct(
        private readonly WorkerShiftPlanPdfBuilder $builder,
        private readonly PdfWrapper $pdf,
        private readonly FilesystemManager $filesystem,
        private readonly InertiaResponseFactory $inertia,
        private readonly UrlGenerator $url,
    ) {
    }

    public function __invoke(WorkerShiftPlanPdfExportRequest $request): Response
    {
        $validated = $request->validated();
        $startDate = Carbon::createFromFormat('Y-m-d', $validated['start'])->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $validated['end'])->endOfDay();
        $matrix = $this->builder->build($startDate, $endDate, $validated);

        /** @var User $user */
        $user = $request->user();
        $title = trim((string) ($validated['title'] ?? '')) ?: __('Shift plan - personnel overview');

        // Kopfbereich weist Kalenderwochen und Gewerke-Filter aus, damit ein
        // gefilterter Export als solcher erkennbar ist.
        $kwRange = ($startDate->isoWeek === $endDate->isoWeek && $startDate->isoWeekYear === $endDate->isoWeekYear)
            ? sprintf('KW %d/%d', $startDate->isoWeek, $startDate->isoWeekYear)
            : sprintf(
                'KW %d/%d - KW %d/%d',
                $startDate->isoWeek,
                $startDate->isoWeekYear,
                $endDate->isoWeek,
                $endDate->isoWeekYear
            );
        $craftFilterNames = !empty($validated['craft_ids'])
            ? Craft::whereIn('id', $validated['craft_ids'])->pluck('name')->all()
            : [];

        $this->pdf
            ->loadView('pdf.shiftplan_worker_matrix', [
                ...$matrix,
                'title' => $title,
                'createdBy' => $user->full_name,
                'createdAt' => Carbon::now()->format('d.m.Y H:i'),
                'startDate' => $startDate->format('d.m.Y'),
                'endDate' => $endDate->format('d.m.Y'),
                'kwRange' => $kwRange,
                'craftFilterNames' => $craftFilterNames,
            ])
            ->setPaper($validated['paperSize'], $validated['paperOrientation'])
            ->setOption('dpi', $validated['dpi']);

        $filename = sprintf('%s_%s.pdf', Carbon::now()->format('Y-m-d'), Str::uuid());
        if ($this->filesystem->directoryMissing('pdf')) {
            $this->filesystem->makeDirectory('pdf');
        }
        $this->pdf->save($this->filesystem->path('pdf/' . $filename));

        return $this->inertia->location(
            $this->url->route('calendar.export.pdf.download', ['filename' => $filename])
        );
    }
}
