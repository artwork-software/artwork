<?php

namespace App\Http\Controllers;

use App\Http\Requests\SeasonSchedulePdfExportRequest;
use Artwork\Core\FileHandling\Naming\StoredFileName;
use Artwork\Modules\Calendar\Services\SeasonSchedulePdfBuilder;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserFilter;
use Barryvdh\Snappy\PdfWrapper;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\ResponseFactory as InertiaResponseFactory;
use Symfony\Component\HttpFoundation\Response;

class SeasonSchedulePdfExportController extends Controller
{
    public function __construct(
        private readonly SeasonSchedulePdfBuilder $builder,
        private readonly PdfWrapper $pdf,
        private readonly FilesystemManager $filesystem,
        private readonly InertiaResponseFactory $inertia,
        private readonly UrlGenerator $url,
    ) {
    }

    public function __invoke(SeasonSchedulePdfExportRequest $request): Response
    {
        $validated = $request->validated();

        /** @var User $user */
        $user = $request->user();

        $startDate = Carbon::createFromFormat('Y-m-d', $validated['startDate'])->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $validated['endDate'])->endOfDay();

        $filter = new UserFilter($validated['filter'] ?? []);
        $filter->exists = false;

        $options = [
            'showHolidays' => $validated['showHolidays'],
            'showWeekNumbers' => $validated['showWeekNumbers'],
            'highlightWeekends' => $validated['highlightWeekends'],
            'showColorDots' => $validated['showColorDots'],
            'showEventsWithoutProject' => $validated['showEventsWithoutProject'],
            'showRoomAbbreviations' => $validated['showRoomAbbreviations'],
        ];

        $scheduleData = $this->builder->build(
            $startDate,
            $endDate,
            $filter,
            $user->getAttribute('calendar_settings'),
            $options
        );

        $title = trim((string) ($validated['title'] ?? ''));
        if ($title === '') {
            $title = $startDate->year === $endDate->year
                ? 'Spielplan ' . $startDate->year
                : sprintf('Spielplan %d/%d', $startDate->year, $endDate->year);
        }

        // Aktive Terminarten-Filter als Legende, damit erkennbar bleibt, was der Ausdruck zeigt
        $eventTypeFilterNames = !empty($filter->event_type_ids)
            ? EventType::whereIn('id', $filter->event_type_ids)->pluck('name')->all()
            : [];

        $generalSettings = app(GeneralSettings::class);
        $bigLogoBase64 = null;
        if ($generalSettings->big_logo_path) {
            $storage = $this->filesystem->disk('public');
            if ($storage->exists($generalSettings->big_logo_path)) {
                $logoContent = $storage->get($generalSettings->big_logo_path);
                $mimeType = $storage->mimeType($generalSettings->big_logo_path);
                $bigLogoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($logoContent);
            }
        }

        $this->pdf
            ->loadView('pdf.seasonScheduleOverview', [
                'title' => $title,
                'pages' => $scheduleData['pages'],
                'periodLabel' => $startDate->format('d.m.Y') . ' – ' . $endDate->format('d.m.Y'),
                'eventTypeFilterNames' => $eventTypeFilterNames,
                'showColorDots' => $options['showColorDots'],
                'splitMonths' => $validated['splitMonths'],
                'created_by' => $user->full_name,
                'created_date' => Carbon::now()->format('d.m.Y'),
                'bigLogoBase64' => $bigLogoBase64,
                'paperSize' => $validated['paperSize'],
            ])
            ->setPaper($validated['paperSize'], 'landscape')
            ->setOption('dpi', $validated['dpi']);

        $filename = StoredFileName::forGenerated('pdf');
        $downloadName = sprintf('%s_%s.pdf', Carbon::now()->format('d.m.Y'), Str::uuid());

        if ($this->filesystem->directoryMissing('pdf')) {
            $this->filesystem->makeDirectory('pdf');
        }
        $this->pdf->save($this->filesystem->path('pdf/' . $filename));

        return $this->inertia->location(
            $this->url->route(
                'calendar.export.pdf.download',
                ['filename' => $filename, 'name' => $downloadName]
            )
        );
    }
}
