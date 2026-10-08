<?php

namespace Artwork\Modules\AppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ShiftSettings;
use Artwork\Modules\AppApi\Enums\WorkerType;
use Artwork\Modules\AppApi\Http\Requests\AppAssignWorkerRequest;
use Artwork\Modules\AppApi\Http\Requests\AppRemoveWorkerRequest;
use Artwork\Modules\AppApi\Services\AppSystemComponentService;
use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Services\ShiftCountService;
use Artwork\Modules\Shift\Services\ShiftFreelancerService;
use Artwork\Modules\Shift\Services\ShiftServiceProviderService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftUserService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppProjectShiftWorkerController extends Controller
{
    // Maximum picker entries per response — the app narrows via ?q= instead
    // of receiving (and silently truncating) the whole directory.
    private const PICKER_LIMIT = 30;

    public function __construct(
        private readonly AppSystemComponentService $systemComponentService,
        private readonly ShiftUserService $shiftUserService,
        private readonly ShiftFreelancerService $shiftFreelancerService,
        private readonly ShiftServiceProviderService $shiftServiceProviderService,
        private readonly ShiftsQualificationsService $shiftsQualificationsService,
        private readonly ShiftSettings $shiftSettings,
        private readonly NotificationService $notificationService,
        private readonly ShiftCountService $shiftCountService,
        private readonly VacationConflictService $vacationConflictService,
        private readonly AvailabilityConflictService $availabilityConflictService,
        private readonly ChangeService $changeService,
    ) {
    }

    /**
     * Candidates for the assignment picker: the shift craft's assigned
     * workers, or everyone when the craft is universally applicable / unset
     * (mirrors the web planner's sidebar scoping). Search happens server-side;
     * the app renders exactly what it gets.
     */
    public function index(Request $request, Project $project, Shift $shift): JsonResponse
    {
        $this->authorizePlanning($project);

        $craft = $shift->craft;
        $craftScoped = $craft !== null && !$craft->universally_applicable;

        $workers = collect([
            ...($craftScoped ? $craft->users : User::query()->orderBy('last_name')->get()),
            ...($craftScoped ? $craft->freelancers : Freelancer::query()->orderBy('last_name')->get()),
            ...($craftScoped ? $craft->serviceProviders : ServiceProvider::query()->orderBy('provider_name')->get()),
        ])->map(static fn (User|Freelancer|ServiceProvider $worker): array => [
            'id' => $worker->id,
            'type' => WorkerType::of($worker)->value,
            'name' => WorkerType::displayName($worker),
        ]);

        $query = trim((string) $request->query('q', ''));
        if ($query !== '') {
            $workers = $workers->filter(
                static fn (array $worker): bool => mb_stripos($worker['name'], $query) !== false,
            )->values();
        }

        return response()->json([
            'workers' => $workers->take(self::PICKER_LIMIT)->values()->all(),
            'has_more' => $workers->count() > self::PICKER_LIMIT,
        ]);
    }

    public function store(AppAssignWorkerRequest $request, Project $project, Shift $shift): JsonResponse
    {
        $this->authorizePlanning($project);

        $workerId = (int) $request->validated('worker_id');
        $qualificationId = (int) $request->validated('shift_qualification_id');
        $isOverbooked = $this->resolveOverbooking($shift, $qualificationId, $request->boolean('overbook'));
        $craftAbbreviation = (string) ($shift->craft?->abbreviation ?? '');

        match ($request->workerType()) {
            WorkerType::User => $this->shiftUserService->assignToShift(
                $shift,
                $workerId,
                $qualificationId,
                $craftAbbreviation,
                $this->notificationService,
                $this->shiftCountService,
                $this->vacationConflictService,
                $this->availabilityConflictService,
                $this->changeService,
                null,
                $isOverbooked,
            ),
            WorkerType::Freelancer => $this->shiftFreelancerService->assignToShift(
                $shift,
                $workerId,
                $qualificationId,
                $craftAbbreviation,
                $this->notificationService,
                $this->shiftCountService,
                $this->vacationConflictService,
                $this->availabilityConflictService,
                $this->changeService,
                null,
                $isOverbooked,
            ),
            WorkerType::ServiceProvider => $this->shiftServiceProviderService->assignToShift(
                $shift,
                $workerId,
                $qualificationId,
                $craftAbbreviation,
                $this->shiftCountService,
                $this->changeService,
                null,
                $isOverbooked,
            ),
        };

        return response()->json(['shift' => $this->systemComponentService->shiftPayload($shift)], 201);
    }

    public function destroy(AppRemoveWorkerRequest $request, Project $project, Shift $shift): JsonResponse
    {
        $this->authorizePlanning($project);

        $workerId = (int) $request->validated('worker_id');

        match ($request->workerType()) {
            WorkerType::User => $this->shiftUserService->removeFromShiftByUserIdAndShiftId(
                $workerId,
                $shift->id,
                $this->notificationService,
                $this->shiftCountService,
                $this->vacationConflictService,
                $this->availabilityConflictService,
                $this->changeService,
            ),
            WorkerType::Freelancer => $this->shiftFreelancerService->removeFromShiftByUserIdAndShiftId(
                $workerId,
                $shift->id,
                $this->notificationService,
                $this->shiftCountService,
                $this->vacationConflictService,
                $this->availabilityConflictService,
                $this->changeService,
            ),
            WorkerType::ServiceProvider => $this->shiftServiceProviderService->removeFromShiftByUserIdAndShiftId(
                $workerId,
                $shift->id,
                $this->shiftCountService,
                $this->changeService,
            ),
        };

        return response()->json(['shift' => $this->systemComponentService->shiftPayload($shift)]);
    }

    /**
     * Mirrors the web planner's overbooking gate: a function with free regular
     * slots is a normal assignment; a full one is only bookable when the
     * instance allows overbooking AND the client explicitly confirmed it. An
     * unconfirmed attempt answers 409 so the app can ask first — never a silent
     * requirement bump.
     */
    private function resolveOverbooking(Shift $shift, int $shiftQualificationId, bool $confirmed): bool
    {
        $required = (int) $shift->shiftsQualifications()
            ->where('shift_qualification_id', $shiftQualificationId)
            ->value('value');

        // A function the shift does not staff at all is not assignable — the
        // web planner hides it for the same reason.
        abort_if($required === 0, 422, 'This function is not part of the shift.');

        $regularlyAssigned = $this->shiftsQualificationsService->getRegularWorkerCount(
            $shift->id,
            $shiftQualificationId,
        );

        if ($regularlyAssigned < $required) {
            return false;
        }

        abort_unless(
            $this->shiftSettings->allow_shift_overbooking,
            422,
            'This function is fully staffed and overbooking is disabled for this instance.',
        );
        abort_unless($confirmed, 409, 'This function is fully staffed — confirm to overbook it.');

        return true;
    }

    private function authorizePlanning(Project $project): void
    {
        $this->authorize('view', $project);
        $this->authorize('plan-shifts');
    }
}
