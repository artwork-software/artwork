<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveShiftMultiEditRequest;
use Artwork\Modules\Availability\Models\AvailabilitiesConflict;
use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Event\Services\EventTimelineService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Freelancer\Services\FreelancerService;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Services\CraftScopeService;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Shift\Models\ShiftCommitWorkflowUser;
use Artwork\Modules\IndividualTimes\Events\IndividualTimeChanged;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\IndividualTimes\Services\IndividualTimeService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Events\AssignUserToShift;
use Artwork\Modules\Shift\Events\CreatedShiftInShiftPlan;
use Artwork\Modules\Shift\Events\DestroyShift;
use Artwork\Modules\Shift\Events\MultiShiftCreateInShiftPlan;
use Artwork\Modules\Shift\Rules\IsoWeekExists;
use Artwork\Modules\Shift\Events\RemoveEntityFormShiftEvent;
use Artwork\Modules\Shift\Events\UpdateEventShiftInShiftPlan;
use Artwork\Modules\Shift\Events\UpdateShiftInShiftPlan;
use Artwork\Modules\Shift\Http\Requests\CopyShiftWeekRequest;
use Artwork\Modules\Shift\Services\ShiftWeekCopyService;
use Artwork\Modules\Shift\Models\ShiftUser;
use Artwork\Modules\Shift\Models\ShiftFreelancer;
use Artwork\Modules\Shift\Models\ShiftServiceProvider;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Services\LegalBreakCalculator;
use Artwork\Modules\Shift\Services\ShiftAssignmentPreflightService;
use Artwork\Modules\Shift\Services\ShiftChangeRecorder;
use Artwork\Modules\Shift\Services\ShiftNotificationLinkService;
use Artwork\Modules\Shift\Services\ShiftCountService;
use Artwork\Modules\Shift\Services\ShiftFreelancerService;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftServiceProviderService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftUserService;
use Artwork\Modules\Shift\Services\ShiftWorkerService;
use Artwork\Modules\Shift\Services\ShiftPlanCommentService;
use Artwork\Modules\Shift\Services\ShiftReplacementService;
use Artwork\Modules\Shift\Models\ShiftPresetTimeline;
use Artwork\Modules\Shift\Services\ShiftRuleService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\User\Services\WorkingHourCacheService;
use Artwork\Modules\Vacation\Models\VacationConflict;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Artwork\Modules\Vacation\Services\VacationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShiftController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ChangeService $changeService,
        private readonly AvailabilityConflictService $availabilityConflictService,
        private readonly VacationConflictService $vacationConflictService,
        private readonly ShiftService $shiftService,
        private readonly Redirector $redirector,
        private readonly IndividualTimeService $individualTimeService,
        private readonly ShiftPlanCommentService $shiftPlanCommentService,
        private readonly VacationService $vacationService,
        private readonly EventTimelineService $eventTimelineService,
        private readonly EventService $eventService,
        private readonly GeneralSettings $generalSettings,
        private readonly WorkingHourCacheService $workingHourCacheService,
    ) {
    }

    public function show(): void
    {
    }

    public function edit(): void
    {
    }

    public function update(Request $request, Shift $shift): RedirectResponse
    {
        if ($shift->is_committed) {
            $event = $shift->event;

            if ($event?->exists) {
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setType('shift')
                        ->setModelClass(Shift::class)
                        ->setModelId($shift->id)
                        ->setShift($shift)
                        ->setTranslationKey('Shift of event has been edited')
                        ->setTranslationKeyPlaceholderValues([$event?->eventName])
                );
            }
        }

        $shift->fill($request->only([
            'start_date',
            'end_date',
            'start',
            'end',
            'break_minutes',
            'craft_id',
            'number_employees',
            'number_masters',
            'description',
        ]));

        $this->shiftService->handleGlobalQualificationChange($request->collect('globalQualifications'), $shift);

        $this->shiftService->save($shift);

        // Re-validate so changed times/break/craft immediately surface (or clear) rule conflicts.
        $this->revalidateShiftRules(
            $shift->users()->get(),
            Carbon::parse($shift->start_date),
            Carbon::parse($shift->end_date)
        );

        return $this->redirector->route('shifts.plan');
    }

    /**
     * Re-run the shift-rule checks for the given users over the given date range so that
     * violations (e.g. HFT/shift conflicts, rest time) surface immediately after a mutation.
     */
    private function revalidateShiftRules(iterable $users, Carbon $start, Carbon $end): void
    {
        $service = app(ShiftRuleService::class);

        foreach ($users as $user) {
            if ($user instanceof User) {
                $service->validateRulesForUser($user, $start->copy(), $end->copy());
            }
        }
    }

    public function updateShift(
        Request $request,
        Shift $shift,
        ShiftsQualificationsService $shiftsQualificationsService,
        ProjectTabService $projectTabService
    ): RedirectResponse {
        // Ohne Validierung landeten end_date < start_date, negative Pausen oder
        // negative Qualifikations-Werte (SQL-Fehler auf smallint unsigned) direkt in der DB.
        $request->validate([
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'break_minutes' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'craft_id' => ['sometimes', 'integer', 'exists:crafts,id'],
            'shiftsQualifications' => ['sometimes', 'array'],
            'shiftsQualifications.*.shift_qualification_id' => ['required_with:shiftsQualifications', 'integer', 'exists:shift_qualifications,id'],
            'shiftsQualifications.*.value' => ['nullable', 'integer', 'min:0'],
        ]);

        $projectId = $shift?->project_id;
        if ($shift->is_committed) {
            $event = $shift?->event;

            if ($event?->exists) {
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setType('shift')
                        ->setModelClass(Shift::class)
                        ->setModelId($shift->id)
                        ->setShift($shift)
                        ->setTranslationKey('Shift of event has been edited')
                        ->setTranslationKeyPlaceholderValues([$event?->eventName])
                );
            }

            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(2);
            $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_CHANGED);

            foreach ($shift->users()->get() as $user) {
                $notificationTitle = __(
                    'notification.shift.locked_changes',
                    [
                        'projectName' => $shift?->event?->project?->name ?? __('notification.shift.without_project'),
                        'craftAbbreviation' => $shift->craft->abbreviation
                    ],
                    $user?->language
                );
                $broadcastMessage = [
                    'id' => Str::uuid()->toString(),
                    'type' => 'error',
                    'message' => $notificationTitle
                ];
                $notificationDescription = [
                    1 => [
                        'type' => 'string',
                        'title' => __('notification.keyWords.concerns_shift', [], $user?->language) .
                            $shift->time_span_label,
                        'href' => null
                    ],
                ];

                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setDescription($notificationDescription);
                $this->notificationService->setNotificationTo($user);
                $this->notificationService->createNotification();
            }

            // Nur Planer:innen des Gewerks (Fallback: Gewerksverantwortliche) — nicht alle
            // Gewerksmitglieder; bereits benachrichtigte Schichtbesetzung wird ausgelassen.
            $notifiedUserIds = $shift->users()->pluck('users.id')->all();

            /** @var User $craftUser */
            foreach ($this->craftPlannersToNotify($shift->craft()->first(), $notifiedUserIds) as $craftUser) {
                if (Auth::id() !== $craftUser->id) {
                    $notificationTitle = __(
                        'notification.shift.locked_changes',
                        [
                            'projectName' => $shift?->event?->project?->name ??
                                __('notification.shift.without_project'),
                            'craftAbbreviation' => $shift->craft->abbreviation
                        ],
                        $craftUser->language
                    );
                    $broadcastMessage = [
                        'id' => Str::uuid()->toString(),
                        'type' => 'error',
                        'message' => $notificationTitle
                    ];
                    $notificationDescription = [
                        1 => [
                            'type' => 'string',
                            'title' => __('notification.keyWords.concerns_shift', [], $craftUser?->language) .
                                $shift->time_span_label,
                            'href' => null
                        ],
                    ];

                    $this->notificationService->setTitle($notificationTitle);
                    $this->notificationService->setBroadcastMessage($broadcastMessage);
                    $this->notificationService->setDescription($notificationDescription);
                    $this->notificationService->setNotificationTo($craftUser);
                    $this->notificationService->createNotification();
                }
            }
        }

        // Mutations-Teil atomar: Save, Worker-Entfernung und Qualifikations-Updates
        // gehören zusammen — bricht ein Schritt ab, bleibt kein halber Zustand zurück.
        DB::transaction(function () use ($request, $shift, $shiftsQualificationsService): void {
            $shift->fill($request->only([
                'start_date',
                'end_date',
                'start',
                'end',
                'break_minutes',
                'craft_id',
                'number_employees',
                'number_masters',
                'description',
                'project_id',
                'shift_group_id',
                'room_id',
            ]));

            $craftChanged = $shift->isDirty('craft_id');

            $this->shiftService->save($shift);

            // When the craft changes, remove all assigned workers since they may not
            // be qualified for the new craft, and reload the craft relation for the broadcast.
            // Über den Service-Pfad statt Bulk-forceDelete: nur so laufen Benachrichtigung
            // der Entfernten, Änderungs-Verlauf, shift_count-Recalc und Cache-Invalidierung.
            if ($craftChanged) {
                $this->removeAllWorkersFromShiftViaService($shift);

                // Legacy-Pivots (werden vom unified Pfad nicht mehr befüllt) aufräumen
                ShiftUser::where('shift_id', $shift->id)->forceDelete();
                ShiftFreelancer::where('shift_id', $shift->id)->forceDelete();
                ShiftServiceProvider::where('shift_id', $shift->id)->forceDelete();

                $shift->unsetRelation('users');
                $shift->unsetRelation('freelancer');
                $shift->unsetRelation('serviceProvider');
                $shift->load('craft:id,name,abbreviation,color');
            }

            // WICHTIG: Nur löschen, wenn das Feld `shiftsQualifications` bewusst leer mitgeschickt wurde
            // (User hat alle Schichtplätze entfernt). Fehlt das Feld komplett im Request – z. B. bei einem
            // partiellen Update wie dem zeitlichen Verschieben einer Schicht – dürfen weder die Schichtplätze
            // noch die Zuweisungen (ShiftWorker = Source of Truth) gelöscht werden.
            if ($request->has('shiftsQualifications') && empty($request->get('shiftsQualifications'))) {
                $this->removeAllWorkersFromShiftViaService($shift);

                ShiftUser::where('shift_id', $shift->id)->forceDelete();
                ShiftFreelancer::where('shift_id', $shift->id)->forceDelete();
                ShiftServiceProvider::where('shift_id', $shift->id)->forceDelete();

                // Model-Deletes statt Bulk-Query: nur so feuert der Observer und die
                // entfernten Schichtplätze erscheinen im Schichtverlauf.
                $shift->shiftsQualifications()->get()->each(
                    static fn ($shiftsQualification) => $shiftsQualification->delete()
                );

                // 3) Eager-Loaded Relation invalidieren, damit Response nicht alte Daten zeigt
                $shift->unsetRelation('users');
            }

            foreach ($request->get('shiftsQualifications', []) as $shiftsQualification) {
                $shiftsQualificationsService->updateShiftsQualificationForShift($shift->id, $shiftsQualification);
            }

            $this->shiftService->handleGlobalQualificationChange($request->collect('globalQualifications'), $shift);
        });

        $projectTab = $projectTabService->findFirstProjectTabWithShiftsComponent();

        if ($shift->event_id) {
            broadcast(new UpdateEventShiftInShiftPlan($shift, $shift->event?->room_id));
        } else {
            broadcast(new UpdateShiftInShiftPlan($shift, $shift->room_id));
        }


        if ($projectTab && $projectId && !$request->boolean('updateOrCreateInShiftPlan')) {
            return $this->redirector->route('projects.tab', [$projectId, $projectTab->id]);
        }


        return $this->redirector->back();
    }

    /**
     * Entfernt alle Zuweisungen einer Schicht über ShiftWorkerService::removeFromShift —
     * im Gegensatz zum früheren Bulk-forceDelete laufen damit Benachrichtigung der
     * Entfernten (bei committed Schichten), Änderungs-Verlauf, shift_count-Recalc
     * und Working-Hour-Cache-Invalidierung.
     */
    private function removeAllWorkersFromShiftViaService(Shift $shift): void
    {
        $shiftWorkerService = app(ShiftWorkerService::class);

        ShiftWorker::where('shift_id', $shift->id)->get()->each(
            fn (ShiftWorker $pivot) => $shiftWorkerService->removeFromShift(
                $pivot,
                true,
                $this->notificationService,
                app(VacationConflictService::class),
                app(AvailabilityConflictService::class),
                $this->changeService
            )
        );
    }

    /**
     * Bulk-Festschreibung (updateCommitments): "Dein Dienstplan {Gewerk} KW n/Jahr wurde
     * festgeschrieben" (NOTIFICATION_SHIFT_LOCKED) — vorher lief hier fälschlich der
     * Zuweisungs-Text shift_staffing. Link öffnet den eigenen Einsatzplan auf der KW der Schicht.
     */
    private function sendShiftLockedNotificationToUser(Shift $shift, User $user): void
    {
        $shiftDate = $shift->start_date ? Carbon::parse($shift->start_date) : Carbon::now();
        $notificationTitle = __(
            'notification.shift.locked_craft_week',
            [
                'craft' => $shift->craft?->name ?? '',
                'week' => $shiftDate->isoWeek(),
                'year' => $shiftDate->isoWeekYear(),
            ],
            $user->language
        );
        $broadcastMessage = [
            'id' => Str::uuid()->toString(),
            'type' => 'success',
            'message' => $notificationTitle
        ];
        $operationPlanLink = ShiftNotificationLinkService::ownOperationPlanForDate($user, $shiftDate);
        $notificationDescription = [
            1 => [
                'type' => 'string',
                'title' => __('notification.keyWords.your_shift', [], $user->language) .
                    $shift->time_span_label,
                'href' => $operationPlanLink,
            ],
            2 => [
                'type' => 'link',
                'title' => __('notification.shift.link_label_own_operation_plan', [], $user->language),
                'href' => $operationPlanLink,
            ],
        ];

        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon('green');
        $this->notificationService->setPriority(3);
        $this->notificationService
            ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_LOCKED);
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setDescription($notificationDescription);
        $this->notificationService->setNotificationTo($user);
        $this->notificationService->createNotification();
        $this->notificationService->clearNotificationData();
    }

    public function updateTime(Request $request, Shift $shift): void
    {
        $request->validate([
            'start' => ['required', 'string'],
            'end' => ['required', 'string'],
            'break_minutes' => ['nullable', 'integer', 'min:0'],
        ]);

        [$start, $end] = $this->eventService->processEventTimesForTimeline(
            Carbon::parse($shift->start_date),
            $request->get('start') ?? null,
            $request->get('end') ?? null
        );

        $shift->start_date = Carbon::parse($start)->format('Y-m-d');
        $shift->end_date = Carbon::parse($end)->format('Y-m-d');
        $shift->start = Carbon::parse($start)->format('H:i:s');
        $shift->end = Carbon::parse($end)->format('H:i:s');
        // Ohne Pause → gesetzliche Mindestpause; ein gesetzter Wert (auch 0) bleibt.
        $shift->break_minutes = LegalBreakCalculator::resolveBreakMinutes(
            $request->get('break_minutes'),
            $shift->start,
            $shift->end
        );

        $this->shiftService->save($shift);
    }

    private function setConflictNotificationHeaderAndData(Shift $shift): void
    {
        $this->notificationService->setIcon('red');
        $this->notificationService->setPriority(2);
        $this->notificationService
            ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_CONFLICT);
        $this->notificationService->setButtons(['see_shift']);
        $this->notificationService->setShiftId($shift->id);
    }

    //@todo: fix phpcs error - complexity too high, nesting too high
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh, Generic.Metrics.NestingLevel.TooHigh
    public function updateCommitments(Request $request, ProjectTabService $projectTabService): RedirectResponse
    {
        $projectId = $request->input('project_id');
        $shiftIds = $request->input('shifts');
        $updateData = $request->only([
            'is_committed',
            'committing_user_id'
        ]);

        $notificationUsers = [];

        $shifts = Shift::whereIn('id', $shiftIds)->get();

        // is_committed ist nicht in logOnly — ohne den Sammel-Eintrag wäre das
        // (Bulk-)Festschreiben bzw. Aufheben im Schichtverlauf unsichtbar.
        $this->shiftService->logCommitSummaryActivity($shifts, $request->boolean('is_committed'));

        foreach ($shifts as $shift) {
            $shift->update($updateData);
            $shiftCommittedBy = $shift->committedBy()->first();
            if ($shift->is_committed) {
                $users = $shift->users()->get();
                foreach ($users as $user) {
                    if (!in_array($user->id, $notificationUsers)) {
                        $this->sendShiftLockedNotificationToUser(
                            shift: $shift,
                            user: $user
                        );
                        $notificationUsers[] = $user->id;
                    }


                    $vacations = $user
                        ->vacations()
                        ->get();
                    $availabilities = $user
                        ->availabilities()
                        ->get();

                    $this->setConflictNotificationHeaderAndData($shift);

                    if ($vacations->count() > 0) {
                        foreach ($vacations as $vacation) {
                            // check if vacation is full_day
                            if ($vacation->full_day) {
                                $this->vacationConflictService->create([
                                    'vacation_id' => $vacation->id,
                                    'shift_id' => $shift->id,
                                    'user_name' => $shiftCommittedBy->full_name,
                                    'date' => $shift->event_start_day,
                                    'start_time' => $shift->start,
                                    'end_time' => $shift->end,
                                ]);
                                $notificationTitle = __(
                                    'notification.shift.conflict',
                                    [],
                                    $user->language
                                );
                                $broadcastMessage = [
                                    'id' => Str::uuid()->toString(),
                                    'type' => 'success',
                                    'message' => $notificationTitle
                                ];
                                $notificationDescription = [
                                    1 => [
                                        'type' => 'string',
                                        /*'title' => $shiftCommittedBy->full_name . ' hat dich am ' .
                                            Carbon::parse($shift->event_start_day)->format('d.m.Y') . ' ' .
                                            $shift->start . ' - ' . $shift->end
                                            . ' eingeplant, entgegen deines ursprünglichen Eintrags.',*/
                                        'title' => __(
                                            'notification.shift.conflict_text',
                                            [
                                                'username' => $shiftCommittedBy->full_name,
                                                'date' => Carbon::parse($shift->event_start_day)->format('d.m.Y'),
                                                'from' => $shift->start,
                                                'to' => $shift->end
                                            ],
                                            $user->language
                                        ),
                                        'href' => ShiftNotificationLinkService::ownOperationPlanForDate($user, $shift->start_date)
                                    ],
                                ];

                                $this->notificationService->setTitle($notificationTitle);
                                $this->notificationService->setBroadcastMessage($broadcastMessage);
                                $this->notificationService->setDescription($notificationDescription);
                                $this->notificationService->setNotificationTo($user);
                                $this->notificationService->createNotification();
                            } else {
                                // check if shift is on vacation time
                                $start = Carbon::parse($vacation->start_time);
                                $end = Carbon::parse($vacation->end_time);
                                if (
                                    $start->between($shift->start, $shift->end) ||
                                    $end->between($shift->start, $shift->end)
                                ) {
                                    $this->vacationConflictService->create([
                                        'vacation_id' => $vacation->id,
                                        'shift_id' => $shift->id,
                                        'user_name' => $shiftCommittedBy->full_name,
                                        'date' => $shift->event_start_day,
                                        'start_time' => $shift->start,
                                        'end_time' => $shift->end,
                                    ]);
                                    $notificationTitle = __(
                                        'notification.shift.conflict',
                                        [],
                                        $user->language
                                    );
                                    $broadcastMessage = [
                                        'id' => Str::uuid()->toString(),
                                        'type' => 'success',
                                        'message' => $notificationTitle
                                    ];
                                    $notificationDescription = [
                                        1 => [
                                            'type' => 'string',
                                            'title' => __(
                                                'notification.shift.conflict_text',
                                                [
                                                    'username' => $shiftCommittedBy->full_name,
                                                    'date' => Carbon::parse($shift->event_start_day)->format('d.m.Y'),
                                                    'from' => $shift->start,
                                                    'to' => $shift->end
                                                ],
                                                $user->language
                                            ),
                                            'href' => ShiftNotificationLinkService::ownOperationPlanForDate($user, $shift->start_date)
                                        ],
                                    ];

                                    $this->notificationService->setTitle($notificationTitle);
                                    $this->notificationService->setBroadcastMessage($broadcastMessage);
                                    $this->notificationService->setDescription($notificationDescription);
                                    $this->notificationService->setNotificationTo($user);
                                    $this->notificationService->createNotification();
                                }
                            }
                        }
                    }

                    if ($availabilities->count() > 0) {
                        foreach ($availabilities as $availability) {
                            // check if shift is before or after availability time
                            $shiftStart = Carbon::parse($shift->start);
                            $shiftEnd = Carbon::parse($shift->end);
                            $availabilityStart = Carbon::parse($availability->start_time);
                            $availabilityEnd = Carbon::parse($availability->end_time);

                            if (
                                $shiftEnd->lessThanOrEqualTo($availabilityStart) ||
                                $shiftStart->greaterThanOrEqualTo($availabilityEnd)
                            ) {
                                $this->availabilityConflictService->create([
                                    'availability_id' => $availability->id,
                                    'shift_id' => $shift->id,
                                    'user_name' => $shiftCommittedBy->full_name,
                                    'date' => $shift->event_start_day,
                                    'start_time' => $shift->start,
                                    'end_time' => $shift->end,
                                ]);
                                $notificationTitle = __(
                                    'notification.shift.conflict',
                                    [],
                                    $user->language
                                );
                                $broadcastMessage = [
                                    'id' => Str::uuid()->toString(),
                                    'type' => 'success',
                                    'message' => $notificationTitle
                                ];
                                $notificationDescription = [
                                    1 => [
                                        'type' => 'string',
                                        'title' => __(
                                            'notification.shift.conflict_text',
                                            [
                                                'username' => $shiftCommittedBy->full_name,
                                                'date' => Carbon::parse($shift->event_start_day)->format('d.m.Y'),
                                                'from' => $shift->start,
                                                'to' => $shift->end
                                            ],
                                            $user->language
                                        ),
                                        'href' => ShiftNotificationLinkService::ownOperationPlanForDate($user, $shift->start_date)
                                    ],
                                ];

                                $this->notificationService->setTitle($notificationTitle);
                                $this->notificationService->setBroadcastMessage($broadcastMessage);
                                $this->notificationService->setDescription($notificationDescription);
                                $this->notificationService->setNotificationTo($user);
                                $this->notificationService->createNotification();
                            } else {
                                $availability->conflicts()->each(function ($conflict): void {
                                    $conflict->delete();
                                });
                            }
                        }
                    }
                }
            } else {
                $shift->update([
                    'committing_user_id' => null
                ]);
                $vacationsConflict = VacationConflict::where('shift_id', $shift->id)->get();
                foreach ($vacationsConflict as $vacationConflict) {
                    $vacationConflict->delete();
                }
                $availabilitiesConflict = AvailabilitiesConflict::where('shift_id', $shift->id)->get();
                foreach ($availabilitiesConflict as $availabilityConflict) {
                    $availabilityConflict->delete();
                }
            }
        }

        if ($projectTab = $projectTabService->findFirstProjectTabWithShiftsComponent()) {
            return $this->redirector->route('projects.tab', [$projectId, $projectTab->id]);
        }

        return $this->redirector->back();
    }

    public function destroy(Shift $shift): void
    {
        if ($shift->is_committed) {
            $event = $shift->event;

            if ($event?->exists) {
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setType('shift')
                        ->setModelClass(Shift::class)
                        ->setModelId($shift->id)
                        ->setShift($shift)
                        ->setTranslationKey('Shift of event was deleted')
                        ->setTranslationKeyPlaceholderValues([$event->eventName])
                );
            }

            $this->notificationService->setIcon('green');
            $this->notificationService->setPriority(3);
            $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_CHANGED);

            foreach ($shift->users()->get() as $user) {
                if (Auth::id() !== $user->id) {
                    $notificationTitle = __(
                        'notification.shift.deleted_where_locked',
                        [
                            'projectName' => $shift?->event?->project?->name ??
                                __('notification.shift.without_project'),
                            'craftAbbreviation' => $shift->craft->abbreviation
                        ],
                        $user->language
                    );
                    $broadcastMessage = [
                        'id' => Str::uuid()->toString(),
                        'type' => 'error',
                        'message' => $notificationTitle
                    ];
                    $notificationDescription = [
                        1 => [
                            'type' => 'string',
                            'title' => __('notification.keyWords.concerns_shift', [], $user->language)
                                . $shift->time_span_label,
                            'href' => null
                        ],
                    ];

                    $this->notificationService->setTitle($notificationTitle);
                    $this->notificationService->setBroadcastMessage($broadcastMessage);
                    $this->notificationService->setDescription($notificationDescription);
                    $this->notificationService->setNotificationTo($user);
                    $this->notificationService->createNotification();
                }
            }

            // Nur Planer:innen des Gewerks (Fallback: Gewerksverantwortliche) — nicht alle
            // Gewerksmitglieder; bereits benachrichtigte Schichtbesetzung wird ausgelassen.
            $notifiedUserIds = $shift->users()->pluck('users.id')->all();

            foreach ($this->craftPlannersToNotify($shift->craft()->first(), $notifiedUserIds) as $craftUser) {
                if (Auth::id() !== $craftUser->id) {
                    $notificationTitle = __(
                        'notification.shift.deleted_where_locked',
                        [
                            'projectName' => $shift?->event?->project?->name ??
                                __('notification.shift.without_project'),
                            'craftAbbreviation' => $shift->craft->abbreviation
                        ],
                        $craftUser->language
                    );
                    $broadcastMessage = [
                        'id' => Str::uuid()->toString(),
                        'type' => 'error',
                        'message' => $notificationTitle
                    ];
                    $notificationDescription = [
                        1 => [
                            'type' => 'string',
                            'title' => __('notification.keyWords.concerns_shift', [], $craftUser->language) .
                                $shift->time_span_label,
                            'href' => null
                        ],
                    ];

                    $this->notificationService->setTitle($notificationTitle);
                    $this->notificationService->setBroadcastMessage($broadcastMessage);
                    $this->notificationService->setDescription($notificationDescription);
                    $this->notificationService->setNotificationTo($craftUser);
                    $this->notificationService->createNotification();
                }
            }

            // conflicts
            $conflicts = VacationConflict::where('shift_id', $shift->id)->get();
            $conflictsAvailability = AvailabilitiesConflict::where('shift_id', $shift->id)->get();

            $conflicts->each(function ($conflict): void {
                $conflict->delete();
            });

            $conflictsAvailability->each(function ($conflict): void {
                $conflict->delete();
            });
        }
        // Capture affected users + range before deletion so we can re-validate afterwards.
        $affectedUsers = $shift->users()->get();
        $shiftStart = Carbon::parse($shift->start_date);
        $shiftEnd = Carbon::parse($shift->end_date);

        broadcast(new DestroyShift(
            $shift,
            $shift->event_id ? $shift->event?->room_id : $shift->room_id
        ));
        $this->shiftService->forceDelete($shift);

        $this->revalidateShiftRules($affectedUsers, $shiftStart, $shiftEnd);
    }

    public function bulkDelete(Request $request): void
    {
        $shiftIds = $request->get('shift_ids', []);
        foreach ($shiftIds as $shiftId) {
            $shift = Shift::find($shiftId);
            if ($shift) {
                $this->destroy($shift);
            }
        }
    }

    public function bulkDuplicate(Request $request): void
    {
        $shiftIds = $request->get('shift_ids', []);
        foreach ($shiftIds as $shiftId) {
            $shift = Shift::with(['shiftsQualifications', 'globalQualifications'])->find($shiftId);
            if (!$shift) {
                continue;
            }

            // Schicht + Qualifikationen atomar duplizieren (kein halbes Duplikat bei Fehlern)
            DB::transaction(function () use ($shift): void {
                // Serien- und Workflow-Identität NICHT mitkopieren: das Duplikat würde
                // sonst an allen "auf Serie anwenden"-Operationen des Originals hängen
                // und einen Workflow-Status tragen, den es nie durchlaufen hat.
                $newShift = $shift->replicate([
                    'deleted_at',
                    'shift_uuid',
                    'in_workflow',
                    'current_request_id',
                    'committing_user_id',
                    'workflow_rejection_reason',
                ]);
                $newShift->is_committed = false;
                $newShift->in_workflow = false;
                $newShift->save();

                foreach ($shift->shiftsQualifications as $sq) {
                    $newShift->shiftsQualifications()->create([
                        'shift_qualification_id' => $sq->shift_qualification_id,
                        'value' => $sq->value,
                    ]);
                }

                foreach ($shift->globalQualifications as $gq) {
                    $newShift->globalQualifications()->attach($gq->id, [
                        'quantity' => $gq->pivot->quantity,
                    ]);
                }
            });
        }
    }

    /**
     * Vorschau für „Woche kopieren": Anzahl der Quellschichten der KW (optional nach Gewerken/Räumen).
     */
    public function copyWeekPreview(
        Request $request,
        ShiftWeekCopyService $shiftWeekCopyService,
        CraftScopeService $craftScopeService
    ): JsonResponse {
        $validated = $request->validate([
            // KW 53 gibt es nur in 53-Wochen-Jahren — sonst würde Carbon still in KW 1 des Folgejahres rollen
            'source_week' => ['required', 'integer', 'min:1', 'max:53', new IsoWeekExists('source_year')],
            'source_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'craft_ids' => ['nullable', 'array', 'max:100'],
            'craft_ids.*' => ['integer', 'exists:crafts,id'],
            'room_ids' => ['nullable', 'array', 'max:100'],
            'room_ids.*' => ['integer', 'exists:rooms,id'],
        ]);

        // Gewerke auf die planbare Menge der Person zuschneiden (leer = genau diese Menge; Admin = alle)
        $craftIds = $craftScopeService->restrictToPlannable(
            $request->user(),
            array_values(array_map('intval', $validated['craft_ids'] ?? []))
        );
        $roomIds = array_values(array_map('intval', $validated['room_ids'] ?? []));
        [$monday, $sunday] = ShiftWeekCopyService::weekBounds(
            (int) $validated['source_week'],
            (int) $validated['source_year']
        );

        $count = $shiftWeekCopyService->sourceShifts(
            (int) $validated['source_week'],
            (int) $validated['source_year'],
            $craftIds,
            $roomIds === [] ? null : $roomIds
        )->count();

        return response()->json([
            'count' => $count,
            'week' => (int) $validated['source_week'],
            'year' => (int) $validated['source_year'],
            'start' => $monday->format('d.m.Y'),
            'end' => $sunday->format('d.m.Y'),
            // Deckel Quellschichten × Zielwochen je Aufruf (Dialog sperrt den Button vorab)
            'max_operations' => ShiftWeekCopyService::MAX_COPY_OPERATIONS,
        ]);
    }

    /**
     * „Woche kopieren": Schichten der Quell-KW in 1–8 Ziel-KWs neu anlegen (ohne Personen,
     * nicht festgeschrieben; belegte Zielzeiten werden übersprungen). Je Zielwoche eine Transaktion.
     */
    public function copyWeek(
        CopyShiftWeekRequest $request,
        ShiftWeekCopyService $shiftWeekCopyService,
        CraftScopeService $craftScopeService
    ): JsonResponse {
        $sourceWeek = (int) $request->input('source_week');
        $sourceYear = (int) $request->input('source_year');

        // Nur planbare Gewerke der Person kopieren (Admin: alle); fremde IDs fallen stillschweigend weg
        $sourceShifts = $shiftWeekCopyService->sourceShifts(
            $sourceWeek,
            $sourceYear,
            $craftScopeService->restrictToPlannable($request->user(), $request->craftIds()),
            $request->roomIds()
        );

        if ($sourceShifts->isEmpty()) {
            throw ValidationException::withMessages([
                'source_week' => __('The source week contains no shifts.'),
            ]);
        }

        // Deckel je Aufruf: Quellschichten × Zielwochen (je Kopie mehrere Inserts + Activity-Log)
        $targets = $request->targets();
        $operations = $sourceShifts->count() * count($targets);
        if ($operations > ShiftWeekCopyService::MAX_COPY_OPERATIONS) {
            throw ValidationException::withMessages([
                'targets' => __(
                    'Too many shifts to copy (:count, maximum :max). Please select fewer crafts or target weeks.',
                    ['count' => $operations, 'max' => ShiftWeekCopyService::MAX_COPY_OPERATIONS]
                ),
            ]);
        }

        $results = [];
        $createdShiftIds = [];
        foreach ($targets as $target) {
            $result = $shiftWeekCopyService->copyToWeek(
                $sourceShifts,
                $sourceWeek,
                $sourceYear,
                $target['week'],
                $target['year']
            );
            $results[] = $result;
            $createdShiftIds = array_merge($createdShiftIds, $result['shift_ids']);
        }

        // Broadcast wie bei der Mehrfachanlage: der Schichtplan-Listener fügt die neuen
        // Schichten in die Zellen ein und bumpt die Raum-Version (sonst bleiben sie unsichtbar).
        if ($createdShiftIds !== []) {
            broadcast(new MultiShiftCreateInShiftPlan(
                Shift::query()->whereIn('id', $createdShiftIds)->get()
            ));
        }

        $summaryLines = array_map(
            static fn (array $result): string => __('CW :week: :created shifts created, :skipped skipped', [
                'week' => $result['week'],
                'created' => $result['created'],
                'skipped' => $result['skipped'],
            ]),
            $results
        );
        $summary = implode(' · ', $summaryLines);

        // Flash für den globalen Toast nach dem anschließenden Plan-Reload
        $request->session()->flash('success', $summary);

        return response()->json([
            'summary' => $summary,
            'source' => [
                'week' => $sourceWeek,
                'year' => $sourceYear,
                'count' => $sourceShifts->count(),
            ],
            'targets' => array_map(
                static fn (array $result): array => [
                    'week' => $result['week'],
                    'year' => $result['year'],
                    'created' => $result['created'],
                    'skipped' => $result['skipped'],
                    'skipped_shifts' => $result['skipped_shifts'],
                ],
                $results
            ),
        ]);
    }

    //phpcs:ignore
    public function saveMultiEdit(
        SaveShiftMultiEditRequest $request,
        ShiftService $shiftService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): bool {
        $validated = $request->validated();
        $shiftsToHandle = $validated['shiftsToHandle'] ?? ['assignToShift' => [], 'removeFromShift' => []];
        $shiftsToHandle['assignToShift'] ??= [];
        $shiftsToHandle['removeFromShift'] ??= [];
        // Ganz-Zeitraum-Projektzuordnungen aus dem Personen-Multiedit (Checkbox
        // "Person dem gesamten Projekt zuweisen" neben dem Projekttitel)
        $fullPeriodProjectIds = $validated['fullPeriodProjectAssignments'] ?? [];

        if (!empty($fullPeriodProjectIds)) {
            $employableType = match ($validated['userType']) {
                1 => Freelancer::class,
                2 => ServiceProvider::class,
                default => User::class,
            };
            $dayAssignmentService = app(\Artwork\Modules\Project\Services\ProjectDayAssignmentService::class);

            // Existenz der Person sicherstellen — sonst entstehen verwaiste Zuordnungszeilen
            foreach (\Artwork\Modules\Project\Models\Project::query()
                ->whereKey($fullPeriodProjectIds)
                ->orderBy('id')
                ->get() as $projectToAssign) {
                $dayAssignmentService->createFullPeriodAssignments(
                    $projectToAssign,
                    $employableType,
                    (int) $validated['userTypeId'],
                    \Artwork\Modules\Project\Enum\ProjectDayAssignmentType::BINDING
                );
            }
        }

        if (empty($shiftsToHandle['assignToShift']) && empty($shiftsToHandle['removeFromShift'])) {
            return !empty($fullPeriodProjectIds);
        }

        $serviceToUse = match ($validated['userType']) {
            0 => $shiftUserService,
            1 => $shiftFreelancerService,
            2 => $shiftServiceProviderService,
            default => null
        };

        if ($serviceToUse === null) {
            return false;
        }

        foreach ($shiftsToHandle['removeFromShift'] as $shiftIdToRemove) {
            if ($serviceToUse instanceof ShiftServiceProviderService) {
                $serviceToUse->removeFromShiftByUserIdAndShiftId(
                    $validated['userTypeId'],
                    $shiftIdToRemove,
                    $shiftCountService,
                    $changeService
                );
            } else {
                $serviceToUse->removeFromShiftByUserIdAndShiftId(
                    $validated['userTypeId'],
                    $shiftIdToRemove,
                    $notificationService,
                    $shiftCountService,
                    $vacationConflictService,
                    $availabilityConflictService,
                    $changeService
                );
            }

            $shift = $shiftService->getById($shiftIdToRemove);
            if (!$shift instanceof Shift) {
                // Ungueltige shiftId im Payload -> kein Fatal auf $shift->refresh()
                continue;
            }

            broadcast(new RemoveEntityFormShiftEvent(
                $shift->refresh(),
                $shift->room_id ?? $shift->event?->room_id,
                $validated['userTypeId'],
                $validated['userType']
            ));
        }

        $allowOverbooking = app(\App\Settings\ShiftSettings::class)->allow_shift_overbooking;

        foreach ($shiftsToHandle['assignToShift'] as $shiftToAssign) {
            $shift = $shiftService->getById($shiftToAssign['shiftId']);

            if (!$shift instanceof Shift) {
                continue;
            }

            $isOverbooked = $allowOverbooking && ($shiftToAssign['isOverbooked'] ?? false);

            // Resolve a valid shift qualification id if not provided
            $resolvedShiftQualificationId = $shiftToAssign['shiftQualificationId'] ?? null;
            if ($resolvedShiftQualificationId === null) {
                // Try to take the first defined qualification for this shift
                $resolvedShiftQualificationId = $shift->shiftsQualifications()->orderBy('id')->value('shift_qualification_id');
                if ($resolvedShiftQualificationId === null) {
                    // Fallback to a generic worker qualification if available
                    $resolvedShiftQualificationId = \Artwork\Modules\Shift\Models\ShiftQualification::available()
                        ->workerQualification()
                        ->orderedByPosition()
                        ->value('id');
                }
            }

            // If no qualification id can be resolved, skip this assignment entry (DB requires it)
            if (!$resolvedShiftQualificationId) {
                continue;
            }

            if ($serviceToUse instanceof ShiftServiceProviderService) {
                $serviceToUse->assignToShift(
                    $shift,
                    $validated['userTypeId'],
                    $resolvedShiftQualificationId,
                    (string) ($validated['craft_abbreviation'] ?? ''),
                    $shiftCountService,
                    $changeService,
                    null,
                    $isOverbooked
                );

                broadcast(new AssignUserToShift(
                    $shift,
                    $shift?->room_id ?? $shift?->event?->room_id,
                    $validated['userTypeId'],
                    $validated['userType']
                ));

                continue;
            }

            $serviceToUse->assignToShift(
                $shift,
                $validated['userTypeId'],
                $resolvedShiftQualificationId,
                (string) ($validated['craft_abbreviation'] ?? ''),
                $notificationService,
                $shiftCountService,
                $vacationConflictService,
                $availabilityConflictService,
                $changeService,
                null,
                $isOverbooked
            );

            broadcast(new AssignUserToShift(
                $shift,
                $shift?->room_id ?? $shift?->event?->room_id,
                $validated['userTypeId'],
                $validated['userType']
            ));
        }

        // Multi-Edit loest sonst keine Regelpruefung aus: nach allen Zu-/Abweisungen einmal
        // den betroffenen Zeitraum neu pruefen, damit Verstoesse sofort erscheinen bzw. verschwinden.
        if ((int) $validated['userType'] === 0) {
            $touchedShiftIds = array_values(array_unique(array_merge(
                array_map('intval', $shiftsToHandle['removeFromShift']),
                array_map(static fn (array $entry): int => (int) $entry['shiftId'], $shiftsToHandle['assignToShift'])
            )));
            $user = User::find($validated['userTypeId']);
            if ($user instanceof User && $touchedShiftIds !== []) {
                $bounds = Shift::withTrashed()
                    ->whereIn('id', $touchedShiftIds)
                    ->selectRaw('MIN(start_date) as min_start, MAX(end_date) as max_end')
                    ->first();
                if ($bounds?->min_start && $bounds?->max_end) {
                    $this->revalidateShiftRules(
                        [$user],
                        Carbon::parse($bounds->min_start),
                        Carbon::parse($bounds->max_end)
                    );
                }
            }
        }

        return true;
    }

    public function assignToShift(
        Shift $shift,
        Request $request,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService,
    ): bool|RedirectResponse {
        if (!auth()->user()?->can('can plan shifts') && !auth()->user()?->hasRole('artwork admin')) {
            abort(403, __('You need the permission "Plan shifts" for this.'));
        }

        // Ohne Validierung führte eine fehlende/unbekannte Qualifikations- oder
        // Worker-ID zu TypeError bzw. FK-Verletzung (500 statt 422).
        $request->validate([
            'userId' => ['required', 'integer'],
            'userType' => ['required', 'integer', Rule::in([0, 1, 2])],
            'shiftQualificationId' => ['required', 'integer', 'exists:shift_qualifications,id'],
        ]);

        $isOverbooked = $request->boolean('isOverbooked');
        if ($isOverbooked && !app(\App\Settings\ShiftSettings::class)->allow_shift_overbooking) {
            abort(
                403,
                __('Overbooking is not active in this organisation. Admins can enable it under Shift settings → Overbooking.')
            );
        }

        $isShiftTab = $request->boolean('isShiftTab');
        $serviceToUse = match ($request->get('userType')) {
            0 => $shiftUserService,
            1 => $shiftFreelancerService,
            2 => $shiftServiceProviderService,
            default => null
        };

        if ($serviceToUse === null) {
            return $isShiftTab ? $this->redirector->back() : false;
        }

        if ($serviceToUse instanceof ShiftServiceProviderService) {
            $serviceToUse->assignToShift(
                $shift,
                $request->get('userId'),
                $request->get('shiftQualificationId'),
                $request->string('craft_abbreviation'),
                $shiftCountService,
                $changeService,
                $request->get('seriesShiftData'),
                $isOverbooked
            );

            broadcast(new AssignUserToShift(
                $shift,
                $shift->event_id ? $shift->event?->room_id : $shift->room_id,
                $request->get('userId'),
                $request->get('userType')
            ));

            return $isShiftTab ? $this->redirector->back() : true;
        }

        $serviceToUse->assignToShift(
            $shift,
            $request->get('userId'),
            $request->get('shiftQualificationId'),
            $request->string('craft_abbreviation'),
            $notificationService,
            $shiftCountService,
            $vacationConflictService,
            $availabilityConflictService,
            $changeService,
            $request->get('seriesShiftData'),
            $isOverbooked
        );

        // Immediately re-validate shift rules for users so HFT/shift conflicts surface right after assignment.
        if ($request->get('userType') === 0) {
            $assignedUser = User::find($request->get('userId'));
            if ($assignedUser) {
                $this->revalidateShiftRules(
                    [$assignedUser],
                    Carbon::parse($shift->start_date),
                    Carbon::parse($shift->end_date)
                );
            }
        }

        broadcast(new AssignUserToShift(
            $shift,
            $shift->event_id ? $shift->event?->room_id : $shift->room_id,
            $request->get('userId'),
            $request->get('userType')
        ));

        return $isShiftTab ? $this->redirector->back() : true;
    }

    /**
     * Vorabprüfung vor dem Drop (Überschneidung, Urlaub, nicht verfügbar) — nur
     * Warnung, die Zuweisung selbst läuft weiterhin über assignToShift.
     */
    public function assignmentPreflight(
        Request $request,
        ShiftAssignmentPreflightService $preflightService
    ): JsonResponse {
        if (!auth()->user()?->can('can plan shifts') && !auth()->user()?->hasRole('artwork admin')) {
            abort(403, __('You need the permission "Plan shifts" for this.'));
        }

        $validated = $request->validate([
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'employable_type' => ['required', Rule::in(['user', 'freelancer', 'service_provider', '0', '1', '2'])],
            'employable_id' => ['required', 'integer'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'start' => ['nullable', 'date_format:H:i'],
            'end' => ['nullable', 'date_format:H:i'],
        ]);

        $shift = Shift::query()->with('craft')->findOrFail($validated['shift_id']);
        $morphClass = ShiftAssignmentPreflightService::morphClassFor((string) $validated['employable_type']);
        $worker = $morphClass ? $morphClass::find($validated['employable_id']) : null;

        if ($worker === null) {
            abort(422, __('The selected person could not be found.'));
        }

        return response()->json($preflightService->check(
            $shift,
            $worker,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
            $validated['start'] ?? null,
            $validated['end'] ?? null,
        ));
    }

    public function removeFromShift(
        int $usersPivotId,
        int $userType,
        Request $request,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): bool|RedirectResponse|null {
        $isShiftTab = $request->boolean('isShiftTab');
        $serviceToUse = match ($userType) {
            0 => $shiftUserService,
            1 => $shiftFreelancerService,
            2 => $shiftServiceProviderService,
            default => null
        };

        if ($serviceToUse === null) {
            return $isShiftTab ? $this->redirector->back() : null;
        }

        // Check if the pivot record still exists before proceeding
        try {
            $shift = $serviceToUse->getShiftByUserPivotId($usersPivotId);
        } catch (\Exception $e) {
            // Pivot record doesn't exist anymore (likely already deleted)
            // Return success response since the goal (removing user from shift) is already achieved
            return $isShiftTab ? $this->redirector->back() : null;
        }

        // Additional check in case the method returns null instead of throwing exception
        if (!$shift) {
            return $isShiftTab ? $this->redirector->back() : null;
        }

        // Capture the removed user (user-type only) before removal so we can re-validate afterwards.
        // Pivot ids reference the unified shift_workers table; fall back to the legacy pivot.
        $removedUserId = null;
        if ($userType === 0) {
            $removedUserId = ShiftWorker::query()
                ->where('id', $usersPivotId)
                ->where('employable_type', User::class)
                ->value('employable_id')
                ?? \Artwork\Modules\Shift\Models\ShiftUser::where('id', $usersPivotId)->value('user_id');
        }

        if ($serviceToUse instanceof ShiftServiceProviderService) {
            $serviceToUse->removeFromShift(
                $usersPivotId,
                $request->boolean('removeFromSingleShift'),
                $shiftCountService,
                $changeService
            );

            broadcast(new RemoveEntityFormShiftEvent(
                $shift,
                $shift->event_id ? $shift->event?->room_id : $shift->room_id,
                $usersPivotId,
                $userType
            ));

            return $isShiftTab ? $this->redirector->back() : null;
        }

        $serviceToUse->removeFromShift(
            $usersPivotId,
            $request->boolean('removeFromSingleShift'),
            $notificationService,
            $shiftCountService,
            $vacationConflictService,
            $availabilityConflictService,
            $changeService
        );

        broadcast(new RemoveEntityFormShiftEvent(
            $shift,
            $shift->event_id ? $shift->event?->room_id : $shift->room_id,
            $usersPivotId,
            $userType
        ));

        // Re-validate the removed user so now-obsolete conflicts can be re-evaluated for the shift's range.
        if ($removedUserId) {
            $removedUser = User::find($removedUserId);
            if ($removedUser) {
                $this->revalidateShiftRules(
                    [$removedUser],
                    Carbon::parse($shift->start_date),
                    Carbon::parse($shift->end_date)
                );
            }
        }

        return $isShiftTab ? $this->redirector->back() : null;
    }

    /**
     * „Ersatz suchen" nach Absage: Kandidat*innen für den abgesagten Platz
     * (gleiche Berechtigung wie assignToShift: Planungsrecht oder Admin).
     */
    public function replacementCandidates(
        Shift $shift,
        Request $request,
        ShiftReplacementService $shiftReplacementService
    ): JsonResponse {
        $this->authorizeReplacement();

        $validated = $request->validate([
            'shift_worker_id' => ['required', 'integer'],
        ]);

        $declined = $this->findReplacementPivot($shift, (int) $validated['shift_worker_id']);

        $showHours = (bool) $request->user()?->can(PermissionEnum::CAN_VIEW_SHIFT_WORKER_HOURS->value);

        return new JsonResponse($shiftReplacementService->candidatesFor($shift, $declined, $showHours));
    }

    /**
     * Abgesagte Zuweisung in EINER Transaktion durch die Ersatzperson ersetzen
     * (bestehende Remove-/Assign-Pfade, Notifications, Verlauf, Broadcasts).
     * Ein Konflikt der Ersatzperson ist nur Hinweis, kein Sperrgrund.
     */
    public function replaceWorker(
        Shift $shift,
        Request $request,
        ShiftReplacementService $shiftReplacementService,
        NotificationService $notificationService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): JsonResponse {
        $this->authorizeReplacement();

        $validated = $request->validate([
            'shift_worker_id' => ['required', 'integer'],
            'replacement_type' => ['required', 'string', Rule::in(['user', 'freelancer'])],
            'replacement_id' => ['required', 'integer'],
            'shift_qualification_id' => ['nullable', 'integer', 'exists:shift_qualifications,id'],
            'craft_abbreviation' => ['nullable', 'string', 'max:255'],
        ]);

        $declined = $this->findReplacementPivot($shift, (int) $validated['shift_worker_id']);

        $replacementClass = $validated['replacement_type'] === 'user' ? User::class : Freelancer::class;
        $replacement = $replacementClass::query()->find((int) $validated['replacement_id']);

        if ($replacement === null) {
            throw ValidationException::withMessages([
                'replacement_id' => __('The selected person could not be found.'),
            ]);
        }

        if (
            $declined->employable_type === $replacementClass
            && (int) $declined->employable_id === (int) $replacement->id
        ) {
            throw ValidationException::withMessages([
                'replacement_id' => __('The replacement must be a different person.'),
            ]);
        }

        // „Bereits zugewiesen" prüft ShiftReplacementService::replace() unter Zeilensperre (422 mit derselben
        // Meldung, 409 wenn die abgesagte Zuweisung inzwischen weg ist) — keine ungesperrte Vorabprüfung hier.

        $shift->loadMissing('craft');

        $qualificationId = (int) ($validated['shift_qualification_id'] ?? $declined->shift_qualification_id);
        $craftAbbreviation = (string) ($validated['craft_abbreviation'] ?? '');
        if ($craftAbbreviation === '') {
            $craftAbbreviation = (string) ($declined->craft_abbreviation ?? $shift->craft?->abbreviation ?? '');
        }

        $declinedType = $declined->employable_type;
        $declinedId = (int) $declined->employable_id;
        $declinedPivotId = (int) $declined->id;

        $pivot = $shiftReplacementService->replace(
            $shift,
            $declined,
            $replacement,
            $qualificationId,
            $craftAbbreviation,
            $notificationService,
            $vacationConflictService,
            $availabilityConflictService,
            $changeService
        );

        // Regelprüfung wie beim normalen Entfernen/Zuweisen (nur Users haben Regeln).
        $usersToRevalidate = [];
        if ($declinedType === User::class && ($declinedUser = User::find($declinedId))) {
            $usersToRevalidate[] = $declinedUser;
        }
        if ($replacement instanceof User) {
            $usersToRevalidate[] = $replacement;
        }
        if ($usersToRevalidate !== []) {
            $this->revalidateShiftRules(
                $usersToRevalidate,
                Carbon::parse($shift->start_date),
                // end_date kann leer sein (eintägige Altbestände) → Starttag statt "heute"
                Carbon::parse($shift->end_date ?: $shift->start_date)
            );
        }

        $roomId = $shift->event_id ? $shift->event?->room_id : $shift->room_id;
        $typeToInt = static fn (string $class): int => match ($class) {
            User::class => 0,
            Freelancer::class => 1,
            default => 2,
        };

        // Beide Broadcasts wie in removeFromShift/assignToShift → Room-Version wird
        // clientseitig gebumpt, andere Planer*innen sehen den Tausch sofort.
        if ($roomId) {
            broadcast(new RemoveEntityFormShiftEvent(
                $shift,
                $roomId,
                (string) $declinedPivotId,
                (string) $typeToInt($declinedType)
            ));
            broadcast(new AssignUserToShift(
                $shift,
                $roomId,
                (string) $replacement->id,
                (string) $typeToInt($replacementClass)
            ));
        }

        $shift->unsetRelation('users');
        $shift->unsetRelation('freelancer');
        $shift->unsetRelation('serviceProvider');
        $shift->load([
            'shiftsQualifications',
            'globalQualifications',
            'users.globalQualifications',
            'freelancer.globalQualifications',
            'serviceProvider.globalQualifications',
        ]);

        return new JsonResponse([
            'success' => true,
            'shift_worker_id' => $pivot->id,
            'workers' => \Artwork\Modules\Calendar\DTO\ShiftDTO::fromModel($shift)->workers,
        ]);
    }

    private function authorizeReplacement(): void
    {
        if (!auth()->user()?->can('can plan shifts') && !auth()->user()?->hasRole('artwork admin')) {
            abort(403, __('You need the permission "Plan shifts" for this.'));
        }
    }

    /**
     * Abgesagte Zuweisung der Schicht (404, wenn sie nicht/nicht mehr zur Schicht gehört). Ungesperrt –
     * beim Ersetzen lädt ShiftReplacementService::replace() den Satz in der Transaktion mit lockForUpdate neu.
     */
    private function findReplacementPivot(Shift $shift, int $shiftWorkerId): ShiftWorker
    {
        $pivot = ShiftWorker::withoutTrashed()
            ->whereKey($shiftWorkerId)
            ->where('shift_id', $shift->id)
            ->first();

        if ($pivot === null) {
            abort(404, __('The assignment no longer exists.'));
        }

        return $pivot;
    }

    /**
     * Authoritative list of a worker's active shift assignments and individual times that cover
     * the given day. Read straight from the database so it is correct regardless of the (possibly
     * stale) shift-plan frontend state — used to decide whether the availability-status warning
     * must be shown and to drive its list / removal.
     */
    public function dayAssignments(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->can(PermissionEnum::SHIFT_PLANNER->value) === true,
            403,
        );

        $validated = $request->validate([
            'model_type' => ['required', 'integer', 'in:0,1,2'],
            'model_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            // Ziel-Status des Verfügbarkeitswechsels: bestimmt, welche Projekt-
            // zuordnungen/-wünsche der Wechsel auflösen würde (Confirm-Modal)
            'vacation_type' => ['sometimes', 'nullable', 'string', 'max:32'],
        ]);

        $modelClass = match ((int) $validated['model_type']) {
            1 => Freelancer::class,
            2 => ServiceProvider::class,
            default => User::class,
        };
        $modelId = (int) $validated['model_id'];
        $date = Carbon::parse($validated['date'])->toDateString();

        $shifts = ShiftWorker::query()
            ->with(['shift.event', 'shift.craft'])
            ->where('employable_type', $modelClass)
            ->where('employable_id', $modelId)
            ->whereHas('shift', function ($query) use ($date): void {
                $query->whereDate('start_date', '<=', $date)
                    ->whereDate('end_date', '>=', $date);
            })
            ->get()
            ->map(function (ShiftWorker $pivot): ?array {
                $shift = $pivot->shift;
                if ($shift === null) {
                    return null;
                }

                return [
                    'pivot_id' => $pivot->id,
                    'shift_id' => $shift->id,
                    'start' => $shift->start ? Carbon::parse((string) $shift->start)->format('H:i') : null,
                    'end' => $shift->end ? Carbon::parse((string) $shift->end)->format('H:i') : null,
                    'event_name' => $shift->event?->name ?? $shift->event?->eventName,
                    'craft_abbreviation' => $shift->craft?->abbreviation,
                ];
            })
            ->filter()
            ->values();

        $individualTimes = IndividualTime::query()
            ->where('timeable_type', $modelClass)
            ->where('timeable_id', $modelId)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->get()
            ->map(fn (IndividualTime $time): array => [
                'id' => $time->id,
                'title' => $time->title,
                'start_time' => $time->start_time ? Carbon::parse((string) $time->start_time)->format('H:i') : null,
                'end_time' => $time->end_time ? Carbon::parse((string) $time->end_time)->format('H:i') : null,
            ])
            ->values();

        $projectAssignments = ($validated['vacation_type'] ?? null) !== null
            ? app(\Artwork\Modules\Project\Services\ProjectDayAssignmentService::class)
                ->getAssignmentsDissolvedByVacation($modelClass, $modelId, [$date], $validated['vacation_type'])
            : [];

        return response()->json([
            'shifts' => $shifts,
            'individual_times' => $individualTimes,
            'project_assignments' => $projectAssignments,
        ]);
    }

    /**
     * Removes a worker (User/Freelancer/ServiceProvider) from the given shift assignments and
     * deletes the given individual times — used when an availability status of "free"/"not
     * available" is set for a day on which the person still starts shifts or has individual times.
     * Only the explicitly listed pivots/times that actually belong to the worker are touched.
     */
    public function removeWorkerFromDay(
        Request $request,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService,
        WorkingHourCacheService $workingHourCacheService
    ): JsonResponse {
        abort_unless(
            $request->user()?->can(PermissionEnum::SHIFT_PLANNER->value) === true,
            403,
        );

        $validated = $request->validate([
            'model_type' => ['required', 'integer', 'in:0,1,2'],
            'model_id' => ['required', 'integer'],
            'shift_pivot_ids' => ['array'],
            'shift_pivot_ids.*' => ['integer'],
            'individual_time_ids' => ['array'],
            'individual_time_ids.*' => ['integer'],
        ]);

        $modelType = (int) $validated['model_type'];
        $modelId = (int) $validated['model_id'];

        $modelClass = match ($modelType) {
            1 => Freelancer::class,
            2 => ServiceProvider::class,
            default => User::class,
        };
        $serviceToUse = match ($modelType) {
            1 => $shiftFreelancerService,
            2 => $shiftServiceProviderService,
            default => $shiftUserService,
        };

        foreach ($validated['shift_pivot_ids'] ?? [] as $pivotId) {
            $pivot = ShiftWorker::query()
                ->where('id', $pivotId)
                ->where('employable_type', $modelClass)
                ->where('employable_id', $modelId)
                ->first();

            if ($pivot === null) {
                // Already gone or does not belong to this worker — skip silently.
                continue;
            }

            $shift = $pivot->shift;

            if ($serviceToUse instanceof ShiftServiceProviderService) {
                $serviceToUse->removeFromShift($pivot->id, true, $shiftCountService, $changeService);
            } else {
                $serviceToUse->removeFromShift(
                    $pivot->id,
                    true,
                    $notificationService,
                    $shiftCountService,
                    $vacationConflictService,
                    $availabilityConflictService,
                    $changeService,
                );
            }

            if ($shift !== null) {
                broadcast(new RemoveEntityFormShiftEvent(
                    $shift,
                    $shift->event_id ? $shift->event?->room_id : $shift->room_id,
                    $pivotId,
                    $modelType,
                ));
            }
        }

        foreach ($validated['individual_time_ids'] ?? [] as $individualTimeId) {
            $individualTime = IndividualTime::query()
                ->where('id', $individualTimeId)
                ->where('timeable_type', $modelClass)
                ->where('timeable_id', $modelId)
                ->first();

            if ($individualTime === null) {
                continue;
            }

            $owner = $individualTime->timeable;
            $individualTime->delete();

            if ($owner) {
                $workingHourCacheService->forgetForEntity(
                    WorkingHourCacheService::entityType($owner),
                    $owner->id,
                );
                broadcast(new IndividualTimeChanged($owner->id, $modelType));
            }
        }

        return response()->json(['success' => true]);
    }

    public function removeAllShiftUsers(
        Shift $shift,
        ShiftService $shiftService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService,
        NotificationService $notificationService,
        ShiftCountService $shiftCountService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): RedirectResponse {
        $shiftUserService->removeAllUsersFromShift(
            $shift,
            $notificationService,
            $shiftCountService,
            $vacationConflictService,
            $availabilityConflictService,
            $changeService
        );
        $shiftFreelancerService->removeAllFreelancersFromShift(
            $shift,
            $notificationService,
            $shiftCountService,
            $vacationConflictService,
            $availabilityConflictService,
            $changeService
        );
        $shiftServiceProviderService->removeAllServiceProvidersFromShift($shift, $shiftCountService, $changeService);

        if ($shift->is_committed) {
            $shiftService->createRemovedAllUsersFromShiftHistoryEntry($shift, $changeService);
        }

        return $this->redirector->back();
    }

    public function updateDescription(Request $request, Shift $shift): \Illuminate\Http\JsonResponse
    {
        $shift->update($request->only(['description']));

        $roomId = $shift->event?->room_id ?? $shift->room_id;
        if ($roomId) {
            broadcast(new UpdateShiftInShiftPlan($shift->fresh(), $roomId));
        }

        return response()->json(['success' => true]);
    }

    public function updateUserCell(Request $request): void
    {
        $comment = $request->get('comment');
        $vacationType = $request->get('vacation_type');
        $entities = $request->get('entities');
        $individualTimes = $request->get('individual_times');
        $projectId = $request->get('project_id');
        $assignmentProject = $projectId
            ? \Artwork\Modules\Project\Models\Project::findOrFail($projectId)
            : null;

        foreach ($entities as $entity) {
            $modelClass = match ($entity['type']) {
                1 => Freelancer::class,
                2 => ServiceProvider::class,
                default => User::class,
            };

            $entityModel = $modelClass::findOrFail($entity['id']);

            // Process individual times and comments for each day
            foreach ($entity['days'] as $day) {
                foreach ($individualTimes as $time) {
                    $this->individualTimeService->createForModel(
                        $entityModel,
                        $time['title'],
                        $time['start_time'],
                        $time['end_time'],
                        $day,
                        isset($time['break_minutes']) && $time['break_minutes'] !== ''
                            ? (int) $time['break_minutes']
                            : null
                    );
                }

                if (!empty($comment)) {
                    $this->shiftPlanCommentService->addOrUpdateShiftPlanCommentByModel(
                        $entityModel,
                        $comment,
                        $day
                    );
                }
            }

            // Bulk update vacations for all days at once to avoid N+1 queries
            if (!$entityModel instanceof ServiceProvider) {
                $this->vacationService->updateVacationsOfEntityBulk(
                    $vacationType,
                    $modelClass,
                    $entityModel,
                    $entity['days']
                );
            }

            // Verbindliche Projektzuordnung für die selektierten Tage — nach dem
            // Vacation-Update, damit ein gleichzeitig gesetzter Status die frisch
            // angelegte Zuordnung nicht sofort wieder auflöst. Route ist bereits
            // auf "can plan shifts" gegated.
            if ($assignmentProject !== null) {
                app(\Artwork\Modules\Project\Services\ProjectDayAssignmentService::class)->createAssignments(
                    $assignmentProject,
                    $modelClass,
                    (int) $entity['id'],
                    \Artwork\Modules\Project\Enum\ProjectDayAssignmentType::BINDING,
                    $entity['days'],
                    false
                );
            }
        }
    }

    public function deleteMultiEditCell(Request $request): void
    {
        $entities = $request->get('entities');
        $shifts = collect(); // Verwende eine Collection, um alle Shifts zu sammeln

        foreach ($entities as $entity) {
            $modelClass = match ($entity['type']) {
                1 => Freelancer::class,
                2 => ServiceProvider::class,
                default => User::class,
            };

            $entityModel = $modelClass::findOrFail($entity['id']);

            foreach ($entity['days'] as $day) {
                $this->individualTimeService->deleteForModel($entityModel, $day);

                $vacations = collect();
                if (!$entityModel instanceof ServiceProvider) {
                    $vacations = $entityModel->vacations()->where('date', $day)->get();
                }

                if ($vacations->isNotEmpty()) {
                    $this->vacationService->deleteVacationInterval($entityModel, $day);
                }

                $entityModel->shiftPlanComments()->where('date', $day)->delete();

                $dayShifts = $entityModel->shifts()->where('shifts.start_date', $day)->get();
                $this->shiftService->detachFromShifts($dayShifts, $modelClass, $entityModel);

                $shifts = $shifts->merge($dayShifts); // Merge neue Shifts mit den vorherigen
            }
        }

        if ($shifts->isNotEmpty()) {
            broadcast(new MultiShiftCreateInShiftPlan($shifts));
        }
    }


    public function updateTimeLine(Event $event, Request $request): void
    {
        $this->eventTimelineService->updateTimeLines($event, $request->get('dataset'));

        $freshEvent = $event->fresh();
        broadcast(new \Artwork\Modules\Event\Events\EventCreated($freshEvent, $freshEvent?->room_id));
    }

    public function addTimeLine(Event $event, Request $request): void
    {
        $this->eventTimelineService->addTimeLines($event, $request->get('dataset'));

        $freshEvent = $event->fresh();
        broadcast(new \Artwork\Modules\Event\Events\EventCreated($freshEvent, $freshEvent?->room_id));
    }

    public function importTimelinePreset(Event $event, ShiftPresetTimeline $shiftPresetTimeline): void
    {
        $this->eventTimelineService->importTimelinePreset($event, $shiftPresetTimeline);
    }

    public function storeTimelinePresetFormEvent(Event $event, Request $request): void
    {
        $this->eventTimelineService->storeTimelinePresetFromEvent($event, $request->get('name'));
    }

    public function storeShiftWithoutEvent(
        Request $request,
        ShiftsQualificationsService $shiftsQualificationsService
    ): void {
        // Ohne Validierung erzeugte ein fehlendes shiftsQualifications-Feld einen
        // TypeError NACH dem Speichern der Schicht (halbfertige Schicht ohne Plätze).
        $request->validate([
            'craft_id' => ['required', 'integer', 'exists:crafts,id'],
            'day' => ['required', 'date'],
            'start' => ['required', 'string'],
            'end' => ['required', 'string'],
            'break_minutes' => ['nullable', 'integer', 'min:0'],
            'shiftsQualifications' => ['present', 'array'],
            'shiftsQualifications.*.shift_qualification_id' => ['required', 'integer', 'exists:shift_qualifications,id'],
            'shiftsQualifications.*.value' => ['nullable', 'integer', 'min:0'],
        ]);

        $shift = DB::transaction(function () use ($request, $shiftsQualificationsService) {
            $data = $request->all();
            // Ohne Pause → gesetzliche Mindestpause; ein gesetzter Wert (auch 0) bleibt.
            $data['break_minutes'] = LegalBreakCalculator::resolveBreakMinutes(
                $data['break_minutes'] ?? null,
                $data['start'] ?? null,
                $data['end'] ?? null
            );
            $shift = $this->shiftService->createShiftWithoutEventAutomatic(
                craftId: $request->craft_id,
                data: $data,
                day: $request->string('day'),
            );
            $shift->shift_uuid = Str::uuid();

            $this->shiftService->save($shift);

            $this->shiftService->handleGlobalQualificationChange($request->collect('globalQualifications'), $shift);

            foreach ($request->get('shiftsQualifications') as $shiftsQualification) {
                $shiftsQualificationsService->createShiftsQualificationForShift($shift->id, $shiftsQualification);
            }

            return $shift;
        });

        broadcast(new MultiShiftCreateInShiftPlan(collect([$shift->fresh()])));
    }

    public function deleteCalendarCell(
        Request $request,
        ShiftsQualificationsService $shiftsQualificationsService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService
    ): void {
        $entities = $request->get('entities');
        foreach ($entities as $entity) {
            $room = Room::findOrFail($entity['roomId']);

            // find room shift by date
            $roomShifts = $room->shifts()
                ->where('shifts.start_date', Carbon::parse($entity['day'])->format('Y-m-d'))
                ->get();

            $roomShifts->each(function ($roomShift) use (
                $shiftsQualificationsService,
                $shiftUserService,
                $shiftFreelancerService,
                $shiftServiceProviderService
            ): void {
                // Collect affected workers before deleting so the broadcast can notify them
                $affectedWorkers = collect();
                foreach ($roomShift->users as $u) {
                    $affectedWorkers->push(['id' => $u->id, 'type' => 'user']);
                }
                foreach ($roomShift->freelancer as $f) {
                    $affectedWorkers->push(['id' => $f->id, 'type' => 'freelancer']);
                }
                foreach ($roomShift->serviceProvider as $sp) {
                    $affectedWorkers->push(['id' => $sp->id, 'type' => 'service_provider']);
                }

                broadcast(new DestroyShift($roomShift, $roomShift->room_id, $affectedWorkers->all()));

                // Über den Service löschen statt Hard-Detach + Soft-Delete: so werden
                // die Zuweisungen MIT-soft-deleted (Restore möglich), Qualifikationen
                // konsistent behandelt und der Working-Hour-Cache invalidiert.
                $this->shiftService->delete(
                    $roomShift,
                    $shiftsQualificationsService,
                    $shiftUserService,
                    $shiftFreelancerService,
                    $shiftServiceProviderService
                );
            });
        }
    }


    /**
     * Check for collisions in shifts for given people and time range.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkCollisions(Request $request): \Illuminate\Http\JsonResponse
    {

        try {
            $people = $request->input('people', []);
            $shiftId = $request->input('shift_id');
            $startDateRaw = $request->input('start_date');
            $endDateRaw = $request->input('end_date');
            $startRaw = $request->input('start');
            $endRaw = $request->input('end');

            // Validate required parameters
            if (empty($startDateRaw) || empty($endDateRaw) || empty($startRaw) || empty($endRaw)) {
                return response()->json(['error' => 'Missing required parameters'], 400);
            }

            // Aktuelle Schicht Zeiten
            try {
                $currentStart = Carbon::parse(Carbon::parse($startDateRaw)->toDateString() . ' ' . $startRaw);
                $currentEnd = Carbon::parse(Carbon::parse($endDateRaw)->toDateString() . ' ' . $endRaw);
                if ($currentEnd <= $currentStart) {
                    $currentEnd->addDay();
                }
            } catch (\Exception $e) {
                Log::error('Error parsing date/time values', [
                    'start_date' => $startDateRaw,
                    'end_date' => $endDateRaw,
                    'start' => $startRaw,
                    'end' => $endRaw,
                    'error' => $e->getMessage()
                ]);
                return response()->json(['error' => 'Invalid date/time format'], 400);
            }

            // Build lookup maps for batch query to avoid N+1
            $peopleByType = [
                'user' => [],
                'freelancer' => [],
                'service_provider' => [],
            ];

            foreach ($people as $person) {
                if (!isset($person['type']) || !isset($person['id'])) {
                    continue;
                }
                if (isset($peopleByType[$person['type']])) {
                    $peopleByType[$person['type']][] = $person['id'];
                }
            }

            // Scope to shifts that could overlap the current shift's date range
            $scopeStartDate = $currentStart->copy()->subDay()->toDateString();
            $scopeEndDate = $currentEnd->copy()->addDay()->toDateString();

            // Batch load ShiftWorkers scoped by date to avoid loading all historical data
            $allPivots = ShiftWorker::withoutTrashed()
                ->with('shift.craft')
                ->whereHas('shift', function ($q) use ($scopeStartDate, $scopeEndDate) {
                    $q->where('start_date', '<=', $scopeEndDate)
                      ->where('end_date', '>=', $scopeStartDate);
                })
                ->where(function ($query) use ($peopleByType) {
                    if (!empty($peopleByType['user'])) {
                        $query->orWhere(function ($q) use ($peopleByType) {
                            $q->where('employable_type', User::class)
                              ->whereIn('employable_id', $peopleByType['user']);
                        });
                    }
                    if (!empty($peopleByType['freelancer'])) {
                        $query->orWhere(function ($q) use ($peopleByType) {
                            $q->where('employable_type', Freelancer::class)
                              ->whereIn('employable_id', $peopleByType['freelancer']);
                        });
                    }
                    if (!empty($peopleByType['service_provider'])) {
                        $query->orWhere(function ($q) use ($peopleByType) {
                            $q->where('employable_type', ServiceProvider::class)
                              ->whereIn('employable_id', $peopleByType['service_provider']);
                        });
                    }
                })
                ->get()
                ->groupBy(function ($pivot) {
                    $typeKey = match ($pivot->employable_type) {
                        User::class => 'user',
                        Freelancer::class => 'freelancer',
                        ServiceProvider::class => 'service_provider',
                        default => 'unknown'
                    };
                    return $typeKey . '_' . $pivot->employable_id;
                });

            $results = [];
            foreach ($people as $person) {
                if (!isset($person['type']) || !isset($person['id'])) {
                    continue; // Skip invalid person entries
                }

                $type = $person['type'];
                $id = $person['id'];
                $hasCollision = false;
                $collisionShifts = [];

                $pivots = $allPivots->get($type . '_' . $id, collect());

                foreach ($pivots as $pivot) {
                    if (!$shift = $pivot->shift) {
                        continue;
                    }
                    // Effektive Zeiten (Pivot vor Schichtzeit, Mitternacht) — gleiche
                    // Auflösung wie die Drop-Vorabprüfung (ShiftAssignmentPreflightService)
                    $pivotInterval = ShiftAssignmentPreflightService::resolvePivotInterval($pivot, $shift);
                    if ($pivotInterval === null) {
                        continue; // Skip this shift
                    }
                    [$shiftStart, $shiftEnd] = $pivotInterval;
                    // Kollisionslogik - Prüfung aller drei Kollisionsfälle
                    $case1 = $currentStart >= $shiftStart && $currentStart < $shiftEnd;
                    $case2 = $currentEnd > $shiftStart && $currentEnd <= $shiftEnd;
                    $case3 = $currentStart <= $shiftStart && $currentEnd >= $shiftEnd;

                    // Special case for exact time match (mentioned in issue description)
                    // This handles the case where a user is assigned to a shift with the exact same time as the current shift
                    $exactMatch = $currentStart->format('H:i') === $shiftStart->format('H:i') &&
                              $currentEnd->format('H:i') === $shiftEnd->format('H:i') &&
                              $currentStart->toDateString() === $shiftStart->toDateString() &&
                              $currentEnd->toDateString() === $shiftEnd->toDateString();
                    // Check for any collision case OR exact time match
                    if ($case1 || $case2 || $case3 || $exactMatch) {
                        $hasCollision = true;

                        // Für Debugging: Welcher Fall hat die Kollision ausgelöst
                        $collisionCase = '';
                        if ($case1) {
                            $collisionCase .= '1';
                        }
                        if ($case2) {
                            $collisionCase .= '2';
                        }
                        if ($case3) {
                            $collisionCase .= '3';
                        }
                        if ($exactMatch) {
                            $collisionCase .= 'E'; // E for Exact match
                        }
                        $collisionShifts[] = [
                        'id' => $shift->id,
                        'start' => $shiftStart->format('Y-m-d H:i'),
                        'end' => $shiftEnd->format('Y-m-d H:i'),
                        'description' => $shift->description,
                        'craftAbbreviation' => $shift->craft?->abbreviation ?? '',
                        'collisionCase' => $collisionCase,
                        'isExactMatch' => $exactMatch,
                        'shiftStartTime' => $shiftStart->format('H:i'),
                        'shiftEndTime' => $shiftEnd->format('H:i'),
                        'currentStartTime' => $currentStart->format('H:i'),
                        'currentEndTime' => $currentEnd->format('H:i')
                        ];
                    }
                }

            // Double-check that hasCollision is set correctly based on collisionShifts
                if (!$hasCollision && count($collisionShifts) > 0) {
                    // Fix the inconsistency by setting hasCollision to true if there are collision shifts
                    $hasCollision = true;
                }

                $results[] = [
                'id' => $id,
                'type' => $type,
                'hasCollision' => $hasCollision,
                'collisionShifts' => $collisionShifts,
                ];
            }

            return response()->json($results);
        } catch (\Exception $e) {
            Log::error('Unexpected error in checkCollisions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'An unexpected error occurred'], 500);
        }
    }


    public function storeShiftMultiAdd(
        Request $request,
        ShiftsQualificationsService $shiftsQualificationsService
    ): void {
        $request->validate([
            'craft_id' => ['required', 'integer', 'exists:crafts,id'],
            'start' => ['required', 'string'],
            'end' => ['required', 'string'],
            'break_minutes' => ['nullable', 'integer', 'min:0'],
            'roomsAndDatesForMultiEdit' => ['required', 'array'],
            'roomsAndDatesForMultiEdit.*.roomId' => ['required', 'integer', 'exists:rooms,id'],
            'roomsAndDatesForMultiEdit.*.day' => ['required', 'date'],
            'shiftsQualifications' => ['present', 'array'],
            'shiftsQualifications.*.shift_qualification_id' => ['required', 'integer', 'exists:shift_qualifications,id'],
            'shiftsQualifications.*.value' => ['nullable', 'integer', 'min:0'],
        ]);

        $roomsAndDatesForMultiEdit = $request->get('roomsAndDatesForMultiEdit');

        // Alle Schichten + Qualifikationen atomar anlegen: bricht die Quali-Schleife
        // ab, bleiben sonst halbfertige Schichten ohne Plätze zurück.
        $createdShifts = DB::transaction(function () use (
            $request,
            $roomsAndDatesForMultiEdit,
            $shiftsQualificationsService
        ) {
            $createdShifts = collect();
            foreach ($roomsAndDatesForMultiEdit as $roomAndDate) {
                $data = [
                    'start' => $request->get('start'),
                    'end' => $request->get('end'),
                    // Ohne Pause → gesetzliche Mindestpause; ein gesetzter Wert (auch 0) bleibt.
                    'break_minutes' => LegalBreakCalculator::resolveBreakMinutes(
                        $request->get('break_minutes'),
                        $request->get('start'),
                        $request->get('end')
                    ),
                    'description' => $request->get('description'),
                    'room_id' => $roomAndDate['roomId'],
                    'project_id' => $request->get('project_id'),
                    'shift_group_id' => $request->get('shift_group_id'),
                ];

                $shift = $this->shiftService->createShiftWithoutEventAutomatic(
                    craftId: $request->get('craft_id'),
                    data: $data,
                    day: Carbon::parse($roomAndDate['day'])->format('Y-m-d'),
                );

                $this->shiftService->handleGlobalQualificationChange(
                    $request->collect('globalQualifications'),
                    $shift
                );

                $shift->shift_uuid = Str::uuid();
                $this->shiftService->save($shift);
                $createdShifts->add($shift->fresh());
            }

            foreach ($createdShifts as $shiftSave) {
                foreach ($request->get('shiftsQualifications') as $shiftsQualification) {
                    $shiftsQualificationsService->createShiftsQualificationForShift(
                        $shiftSave->id,
                        $shiftsQualification
                    );
                }
            }

            return $createdShifts;
        });

        broadcast(new MultiShiftCreateInShiftPlan($createdShifts));

        // Rückmeldung mit Anzahl für den globalen Flash-Toast (Inertia leitet bei leerer
        // Antwort per onEmptyResponse zurück; die Flash-Nachricht überlebt den Redirect).
        $createdCount = $createdShifts->count();
        $request->session()->flash(
            'success',
            $createdCount === 1
                ? __('1 shift created.')
                : __(':count shifts created.', ['count' => $createdCount])
        );
    }

    public function updateIndividualShiftTime(Request $request)
    {
        $request->validate([
            'shiftPivotId' => ['required', 'integer'],
            'start_time' => ['required', 'string'],
            'end_time' => ['required', 'string'],
        ]);

        $shiftId = $request->get('shiftPivotId');
        $startTime = $request->get('start_time');
        $endTime = $request->get('end_time');

        $pivot = ShiftWorker::withoutTrashed()->find($shiftId);
        if (!$pivot) {
            return response()->json(['error' => 'Shift pivot not found'], 404);
        }

        if (!$pivot->relationLoaded('shift')) {
            $pivot->load('shift');
        }

        // end_date immer aus start_date neu ableiten: das alte Pivot-end_date kann
        // von einer früheren Über-Mitternacht-Zeit stammen (+1 Tag) — bei Korrektur
        // auf eine normale Tageszeit entstand sonst eine 32h-Zuweisung.
        $startDate = Carbon::parse($pivot->start_date ?? $pivot->shift->start_date)->toDateString();
        $startDateTime = Carbon::parse($startDate . ' ' . $startTime);
        $endDateTime = Carbon::parse($startDate . ' ' . $endTime);

        if ($endDateTime <= $startDateTime) {
            $endDateTime->addDay();
        }

        $beforeLabel = ($pivot->start_time || $pivot->end_time)
            ? Carbon::parse($pivot->start_time)->format('H:i') . ' - ' . Carbon::parse($pivot->end_time)->format('H:i')
            : null;

        // Update the pivot with new start and end times
        $pivot->update([
            'start_time' => $startTime,
            'end_time' => $endTime,
            'start_date' => $startDateTime->format('Y-m-d'),
            'end_date' => $endDateTime->format('Y-m-d'),
        ]);

        // Änderung im Workflow-/Festschreibungs-Verlauf protokollieren (B13)
        app(ShiftWorkerService::class)->logIndividualPivotChange(
            $pivot,
            'individual_time',
            $beforeLabel,
            $startDateTime->format('H:i') . ' - ' . $endDateTime->format('H:i')
        );

        // Individuelle Zeit geändert → eine bereits abgegebene Zu-/Absage bezog
        // sich auf die alte Zeit und wird auf "ausstehend" zurückgesetzt.
        if ($pivot->wasChanged(['start_time', 'end_time', 'start_date', 'end_date'])) {
            app(\Artwork\Modules\Shift\Services\ShiftWorkerConfirmationService::class)
                ->resetConfirmation($pivot);
        }

        $this->workingHourCacheService->forgetForEntity(
            WorkingHourCacheService::entityType($pivot->employable),
            $pivot->employable_id
        );

        // Broadcast the updated shift so the frontend updates in real-time
        $pivot->shift->load([
            'shiftsQualifications',
            'globalQualifications',
            'users.globalQualifications',
            'freelancer.globalQualifications',
            'serviceProvider.globalQualifications',
            'project',
        ]);

        if (!$pivot->shift->event_id) {
            broadcast(new UpdateShiftInShiftPlan($pivot->shift, $pivot->shift->room_id));
        } else {
            $pivot->shift->load('event');
            broadcast(new UpdateShiftInShiftPlan($pivot->shift, $pivot->shift->event?->room_id));
        }
    }

    public function updateShortDescription(Request $request): void
    {
        $validated = $request->validate([
            'shiftPivotId' => ['required', 'integer'],
            'entity' => ['required', 'array'],
            'entity.type' => ['required', 'string', Rule::in(['user', 'freelancer', 'service_provider'])],
            'short_description' => ['nullable', 'string', 'max:250'],
        ]);

        $shiftId = $validated['shiftPivotId'];
        $entity = $validated['entity'];

        $pivot = ShiftWorker::withoutTrashed()->find($shiftId);
        if (!$pivot) {
            return;
        }

        // Spiegelt canEdit der ShiftNoteComponent: Planer:innen dürfen alle Notizen
        // bearbeiten, sonst nur die eigene (User-Pivot des Requesters).
        // Admins passieren den can()-Check bereits über Gate::before.
        $authUser = auth()->user();
        $isOwnPivot = $pivot->employable_type === User::class
            && (int) $pivot->employable_id === (int) $authUser?->id;
        if (!$isOwnPivot && !$authUser?->can(PermissionEnum::SHIFT_PLANNER->value)) {
            abort(403, __('You need the permission "Plan shifts" for this.'));
        }

        $beforeDescription = $pivot->short_description;

        $pivot->update([
            'short_description' => $validated['short_description'],
        ]);

        // Änderung im Workflow-/Festschreibungs-Verlauf protokollieren (B13)
        app(ShiftWorkerService::class)->logIndividualPivotChange(
            $pivot,
            'worker_short_description',
            $beforeDescription,
            $validated['short_description'] ?? null
        );

        if (!$pivot->relationLoaded('shift')) {
            $pivot->load('shift');
        }

        // Broadcast the updated shift
        if (!$pivot->shift->event_id) {
            broadcast(new UpdateShiftInShiftPlan($pivot->shift, $pivot->shift->room_id));
        } else {
            broadcast(new UpdateShiftInShiftPlan($pivot->shift, $pivot->shift->event?->room_id));
        }
    }

    /**
     * Planer:innen eines Gewerks für Benachrichtigungen nach Festschreibung:
     * craftShiftPlaner, sonst managingUsers; ohne Gewerk leer. $excludeUserIds
     * verhindert Doppel-Benachrichtigungen an bereits informierte Personen.
     *
     * @param int[] $excludeUserIds
     * @return Collection<int, User>
     */
    private function craftPlannersToNotify(?Craft $craft, array $excludeUserIds = []): Collection
    {
        if ($craft === null) {
            return new Collection();
        }

        $planners = $craft->craftShiftPlaner()->get();
        if ($planners->isEmpty()) {
            $planners = $craft->managingUsers()->get();
        }

        return $planners
            ->reject(static fn (User $user): bool => in_array($user->id, $excludeUserIds, true))
            ->unique('id')
            ->values();
    }

    public function updateWorkflowSettings(Request $request): RedirectResponse
    {
        $enabled = $request->boolean('shift_commit_workflow');

        // Ohne Genehmiger:in laufen Freigabe-Anfragen ins Leere — Aktivieren erst,
        // wenn mindestens eine Person eingetragen ist (422 per ValidationException,
        // damit Inertia die Meldung als Feldfehler zurückspielt).
        if ($enabled && !ShiftCommitWorkflowUser::query()->exists()) {
            throw ValidationException::withMessages([
                'shift_commit_workflow' => __('Please add at least one person as approver first.'),
            ]);
        }

        $this->generalSettings->shift_commit_workflow_enabled = $enabled;
        $this->generalSettings->save();

        return back();
    }

}
