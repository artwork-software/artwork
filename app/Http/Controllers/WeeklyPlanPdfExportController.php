<?php

namespace App\Http\Controllers;

use App\Http\Requests\WeeklyPlanBulkPdfExportRequest;
use Artwork\Core\FileHandling\Naming\StoredFileName;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Services\WeeklyPlanPdfBuilder;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Policies\UserPolicy;
use Artwork\Modules\User\Services\UserService;
use Barryvdh\Snappy\PdfWrapper;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\ResponseFactory as InertiaResponseFactory;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wochenplan als PDF: je Person eine Seite mit der gewählten Kalenderwoche und der unverbindlichen
 * Vorschau auf die Folgewoche. Einzeln aus dem Einsatzplan einer Person (gleiche Sichtregel wie das
 * Monats-PDF) oder als Sammel-PDF aus dem Schichtplan.
 */
class WeeklyPlanPdfExportController extends Controller
{
    public function __construct(
        private readonly WeeklyPlanPdfBuilder $builder,
        private readonly PdfWrapper $pdf,
        private readonly FilesystemManager $filesystem,
        private readonly InertiaResponseFactory $inertia,
        private readonly UrlGenerator $url,
        private readonly UserService $userService,
    ) {
    }

    /**
     * Wochenplan einer Person. {user} ist je nach type eine User-, Freelancer- oder Dienstleister-ID,
     * deshalb wie beim Monats-PDF kein Route-Model-Binding.
     */
    public function single(Request $request, int $user): Response
    {
        // Wie beim Monats-PDF zuerst die Sichtregel, dann die Eingaben: wer den Plan nicht sehen darf,
        // erfährt auch nichts über die erwarteten Felder.
        $type = $request->string('type', 'user')->toString();
        if ($type === 'serviceProvider') {
            $type = 'service_provider';
        }
        $modelClass = WeeklyPlanPdfBuilder::WORKER_MODELS[$type] ?? User::class;
        /** @var User|Freelancer|ServiceProvider $worker */
        $worker = $modelClass::query()->findOrFail((int) ($request->integer('model_id') ?: $user));

        /** @var User $authUser */
        $authUser = $request->user();

        // Gleiche Sichtregel wie Einsatzplan und Monats-PDF (UserPolicy::viewOperationPlan): eigener
        // Plan nur mit "can view own roster", fremde nur mit Dienstplan-Sichtrechten.
        $isOwnPlan = $worker instanceof User && $authUser->is($worker);
        if (
            !($isOwnPlan && $authUser->can(PermissionEnum::CAN_VIEW_OWN_ROSTER->value))
            && !UserPolicy::canViewForeignRoster($authUser)
        ) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'week' => ['required', 'date_format:Y-m-d'],
            'paperSize' => ['nullable', Rule::in(['a3', 'a4'])],
            'paperOrientation' => ['nullable', Rule::in(['landscape', 'portrait'])],
        ]);

        // Eigener Plan: nicht verbindliche Schichten ggf. ausblenden (Instanz-Einstellung)
        $committedOnly = $worker instanceof User
            && $this->userService->shouldHideUncommittedShiftsInOwnRoster($worker);

        $page = $this->builder->buildPage(
            $worker,
            Carbon::createFromFormat('Y-m-d', $validated['week']),
            $committedOnly
        );

        return $this->render(
            [$page],
            __('Weekly plan'),
            $authUser,
            $validated['paperSize'] ?? 'a4',
            $validated['paperOrientation'] ?? 'landscape',
            96
        );
    }

    /**
     * Sammel-PDF: je Person eine Seite. Personen ohne Einträge in der Woche entfallen, wenn sie nicht
     * ausdrücklich gewünscht sind.
     */
    public function bulk(WeeklyPlanBulkPdfExportRequest $request): Response
    {
        $validated = $request->validated();
        $weekStart = Carbon::createFromFormat('Y-m-d', $validated['week']);

        $workers = $this->builder->resolveWorkers(
            $validated['workers'] ?? [],
            $validated['worker_types'],
            array_map('intval', $validated['craft_ids'] ?? [])
        );

        if ($workers->count() > WeeklyPlanPdfBuilder::MAX_WORKERS) {
            throw ValidationException::withMessages([
                'workers' => __(
                    'The weekly plan can be created for at most :max people at once. Please narrow the selection.',
                    ['max' => WeeklyPlanPdfBuilder::MAX_WORKERS]
                ),
            ]);
        }

        $includeEmpty = (bool) $validated['include_empty_workers'] || !empty($validated['workers']);
        $pages = [];
        foreach ($workers as $worker) {
            $page = $this->builder->buildPage($worker, $weekStart);
            if ($includeEmpty || $page['hasEntries']) {
                $pages[] = $page;
            }
        }

        /** @var User $authUser */
        $authUser = $request->user();

        return $this->render(
            $pages,
            trim((string) ($validated['title'] ?? '')) ?: __('Weekly plan'),
            $authUser,
            $validated['paperSize'],
            $validated['paperOrientation'],
            (int) $validated['dpi']
        );
    }

    /**
     * @param list<array<string, mixed>> $pages
     */
    private function render(
        array $pages,
        string $title,
        User $authUser,
        string $paperSize,
        string $paperOrientation,
        int $dpi
    ): Response {
        $this->pdf
            ->loadView('pdf.weekly_plan', [
                'pages' => $pages,
                'title' => $title,
                'createdBy' => $authUser->full_name,
                'createdAt' => Carbon::now()->format('d.m.Y H:i'),
                'bigLogoBase64' => $this->logo(),
            ])
            ->setPaper($paperSize, $paperOrientation)
            ->setOption('dpi', $dpi);

        $filename = StoredFileName::forGenerated('pdf');
        $downloadName = sprintf('%s_%s.pdf', Carbon::now()->format('Y-m-d'), Str::uuid());
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

    private function logo(): ?string
    {
        $path = app(GeneralSettings::class)->big_logo_path;
        if (!$path) {
            return null;
        }

        $storage = $this->filesystem->disk('public');
        if (!$storage->exists($path)) {
            return null;
        }

        return 'data:' . $storage->mimeType($path) . ';base64,' . base64_encode((string) $storage->get($path));
    }
}
