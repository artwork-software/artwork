<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Events\ProjectTeamUpdated;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Der eine Schreibpfad für die Projekt-Teamzusammensetzung — Web (Roster-Sync)
 * und App (Einzeländerungen) laufen beide hierüber und erhalten damit
 * dieselben Verlaufseinträge, Benachrichtigungen und Broadcasts.
 */
class ProjectTeamService
{
    public function __construct(
        private readonly ChangeService $changeService,
        private readonly NotificationService $notificationService,
    ) {
    }

    /**
     * Projektleitung hat laut ProjectPolicy::update immer Schreibrecht — der Pivot spiegelt das,
     * damit Listen/Exports (writeUsers) nicht vom Frontend-Häkchen abhängen.
     *
     * @template TPivot of array<string, mixed>
     * @param TPivot $pivot
     * @return TPivot
     */
    public static function withManagerWriteRight(array $pivot): array
    {
        if (!empty($pivot['is_manager'])) {
            $pivot['can_write'] = true;
        }

        return $pivot;
    }

    /**
     * Runs a roster mutation with the full set of side effects the web team
     * editor performs: history entries for department changes, notifications
     * for gained/lost roles and the live team-updated broadcast.
     */
    public function applyRosterChange(Project $project, Closure $mutation): void
    {
        $managersBefore = $project->managerUsers()->get();
        $budgetAccessBefore = $project->access_budget()->get();
        $usersBefore = $project->users()->get();
        $departmentsBefore = $project->departments()->get();

        $mutation($project);

        $this->checkDepartmentChanges($project->id, $departmentsBefore, $project->departments()->get());
        $this->createNotificationProjectMemberChanges(
            $project,
            $managersBefore,
            $usersBefore,
            $project->users()->get(),
            $project->managerUsers()->get(),
            $budgetAccessBefore,
            $project->access_budget()->get(),
        );

        broadcast(new ProjectTeamUpdated($project->id));
    }

    /**
     * @param Collection<int, Department> $oldDepartments
     * @param Collection<int, Department> $newDepartments
     */
    private function checkDepartmentChanges(
        int $projectId,
        Collection $oldDepartments,
        Collection $newDepartments,
    ): void {
        $oldDepartmentIds = [];
        $newDepartmentIds = [];
        $oldDepartmentNames = [];
        foreach ($oldDepartments as $oldDepartment) {
            $oldDepartmentIds[] = $oldDepartment->id;
            $oldDepartmentNames[$oldDepartment->id] = $oldDepartment->name;
        }

        foreach ($newDepartments as $newDepartment) {
            $newDepartmentIds[] = $newDepartment->id;
            if (!in_array($newDepartment->id, $oldDepartmentIds)) {
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setModelClass(Project::class)
                        ->setModelId($projectId)
                        ->setTranslationKey('Department added to project team')
                        ->setTranslationKeyPlaceholderValues([$newDepartment->name])
                );
            }
        }

        foreach ($oldDepartmentIds as $oldDepartmentId) {
            if (!in_array($oldDepartmentId, $newDepartmentIds)) {
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setModelClass(Project::class)
                        ->setModelId($projectId)
                        ->setTranslationKey('Department removed from project team')
                        ->setTranslationKeyPlaceholderValues([$oldDepartmentNames[$oldDepartmentId]])
                );
            }
        }
    }

    //@todo: fix phpcs error - refactor function because complexity exceeds allowed maximum
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
    private function createNotificationProjectMemberChanges(
        Project $project,
        $projectManagerBefore,
        $projectUsers,
        $projectUsersAfter,
        $projectManagerAfter,
        $projectBudgetAccessBefore,
        $projectBudgetAccessAfter
    ): void {
        $userIdsBefore = [];
        $managerIdsBefore = [];
        $budgetIdsBefore = [];
        $userIdsAfter = [];
        $managerIdsAfter = [];
        $budgetIdsAfter = [];

        foreach ($projectUsers as $projectUser) {
            $userIdsBefore[$projectUser->id] = $projectUser->id;
        }

        foreach ($projectManagerBefore as $managerBefore) {
            $managerIdsBefore[$managerBefore->id] = $managerBefore->id;
            if (in_array($managerBefore->id, $userIdsBefore)) {
                unset($userIdsBefore[$managerBefore->id]);
            }
        }
        foreach ($projectBudgetAccessBefore as $budgetBefore) {
            $budgetIdsBefore[$budgetBefore->id] = $budgetBefore->id;
            if (in_array($budgetBefore->id, $userIdsBefore)) {
                unset($userIdsBefore[$budgetBefore->id]);
            }
        }
        foreach ($projectUsersAfter as $projectUserAfter) {
            $userIdsAfter[$projectUserAfter->id] = $projectUserAfter->id;
        }

        foreach ($projectManagerAfter as $managerAfter) {
            $managerIdsAfter[$managerAfter->id] = $managerAfter->id;
            // if added a new project manager, send notification to this user
            if (!in_array($managerAfter->id, $managerIdsBefore)) {
                $notificationTitle = __('notification.project.leader.add', [
                    'project' => $project->name
                ], $managerAfter->language);
                $broadcastMessage = [
                    'id' => Str::uuid()->toString(),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('green');
                $this->notificationService->setPriority(3);
                $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_PROJECT);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setProjectId($project->id);
                $this->notificationService->setNotificationTo($managerAfter);
                $this->notificationService->createNotification();
            }
            if (in_array($managerAfter->id, $userIdsAfter)) {
                unset($userIdsAfter[$managerAfter->id]);
            }
        }

        foreach ($projectBudgetAccessAfter as $budgetAfter) {
            $budgetIdsAfter[$budgetAfter->id] = $budgetAfter->id;
            // if added a new project manager, send notification to this user
            if (!in_array($budgetAfter->id, $budgetIdsBefore)) {
                $notificationTitle = __('notification.project.budget.add', [
                    'project' => $project->name
                ], $budgetAfter->language);
                $broadcastMessage = [
                    'id' => Str::uuid()->toString(),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('green');
                $this->notificationService->setPriority(3);
                $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setProjectId($project->id);
                $this->notificationService->setNotificationTo($budgetAfter);
                $this->notificationService->createNotification();
            }
            if (in_array($budgetAfter->id, $userIdsAfter)) {
                unset($userIdsAfter[$budgetAfter->id]);
            }
        }

        foreach ($managerIdsBefore as $managerBefore) {
            if (!in_array($managerBefore, $managerIdsAfter)) {
                $user = User::find($managerBefore);
                if ($user === null) {
                    continue;
                }
                $notificationTitle = __('notification.project.leader.remove', [
                    'project' => $project->name
                ], $user->language);
                $broadcastMessage = [
                    'id' => Str::uuid()->toString(),
                    'type' => 'error',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('red');
                $this->notificationService->setPriority(2);
                $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_PROJECT);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setProjectId($project->id);
                $this->notificationService->setNotificationTo($user);
                $this->notificationService->createNotification();
            }
        }
        foreach ($budgetIdsBefore as $budgetBefore) {
            if (!in_array($budgetBefore, $budgetIdsAfter)) {
                $user = User::find($budgetBefore);
                if ($user === null) {
                    continue;
                }
                $notificationTitle = __('notification.project.budget.remove', [
                    'project' => $project->name
                ], $user->language);
                $broadcastMessage = [
                    'id' => Str::uuid()->toString(),
                    'type' => 'error',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('red');
                $this->notificationService->setPriority(2);
                $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setProjectId($project->id);
                $this->notificationService->setNotificationTo($user);
                $this->notificationService->createNotification();
            }
        }
        foreach ($userIdsAfter as $userIdAfter) {
            if (!in_array($userIdAfter, $userIdsBefore)) {
                $user = User::find($userIdAfter);
                if ($user === null) {
                    continue;
                }
                $notificationTitle = __('notification.project.member.add', [
                    'project' => $project->name
                ], $user->language);
                $broadcastMessage = [
                    'id' => Str::uuid()->toString(),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('green');
                $this->notificationService->setPriority(3);
                $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_PROJECT);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setProjectId($project->id);
                $this->notificationService->setNotificationTo($user);
                $this->notificationService->createNotification();

                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setModelClass(Project::class)
                        ->setModelId($project->id)
                        ->setTranslationKey('User added to project team')
                        ->setTranslationKeyPlaceholderValues([$user->first_name . ' ' . $user->last_name])
                );
            }
        }
        foreach ($userIdsBefore as $userIdBefore) {
            if (!in_array($userIdBefore, $userIdsAfter)) {
                $user = User::find($userIdBefore);
                if ($user === null) {
                    continue;
                }
                $notificationTitle = __('notification.project.member.remove', [
                    'project' => $project->name
                ], $user->language);
                $broadcastMessage = [
                    'id' => Str::uuid()->toString(),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('red');
                $this->notificationService->setPriority(2);
                $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_PROJECT);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setProjectId($project->id);
                $this->notificationService->setNotificationTo($user);
                $this->notificationService->createNotification();

                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setType('public_changes')
                        ->setModelClass(Project::class)
                        ->setModelId($project->id)
                        ->setTranslationKey('User removed from project team')
                        ->setTranslationKeyPlaceholderValues([$user->first_name . ' ' . $user->last_name])
                );
            }
        }
    }
}
