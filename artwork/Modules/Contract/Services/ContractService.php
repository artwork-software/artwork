<?php

namespace Artwork\Modules\Contract\Services;

use Artwork\Core\FileHandling\Upload\ArtworkFileTypes;
use Artwork\Core\FileHandling\Upload\HandlesFileUpload;
use Artwork\Modules\Contract\Exports\ContractExcelExport;
use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Contract\Repositories\ContractRepository;
use Artwork\Modules\DocumentRequest\Models\DocumentRequest;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\GeneralSettings\Services\GeneralSettingsService;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContractFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractService
{
    use HandlesFileUpload;

    public function __construct(
        private readonly ContractRepository $contractRepository,
        private readonly NotificationService $notificationService,
        private readonly ProjectTabService $projectTabService,
        private readonly GeneralSettingsService $generalSettingsService
    ) {
    }

    public function createContract(array $data, Project $project, UploadedFile $file, ?int $documentRequestId = null): Contract
    {
        if (!Storage::exists("contracts")) {
            Storage::makeDirectory("contracts");
        }

        $this->handleFile(ArtworkFileTypes::CONTRACT, $file);
        $originalName = $file->getClientOriginalName();
        $basename = Str::random(20) . $originalName;

        Storage::putFileAs('contracts', $file, $basename);

        $contract = $project->contracts()->create([
            'name' => $originalName,
            'basename' => $basename,
            'creator_id' => Auth::id(),
            'contract_partner' => $data['contract_partner'] ?? null,
            'amount' => $data['amount'] ?? null,
            'project_id' => $project->id,
            'currency_id' => $data['currency_id'] ?? null,
            'description' => $data['description'] ?? null,
            'ksk_liable' => $data['ksk_liable'] ?? false,
            'ksk_amount' => $data['ksk_amount'] ?? null,
            'ksk_reason' => $data['ksk_reason'] ?? null,
            'resident_abroad' => $data['resident_abroad'] ?? false,
            'foreign_tax' => $data['foreign_tax'] ?? false,
            'foreign_tax_amount' => $data['foreign_tax_amount'] ?? null,
            'foreign_tax_reason' => $data['foreign_tax_reason'] ?? null,
            'reverse_charge_amount' => $data['reverse_charge_amount'] ?? null,
            'deadline_date' => $data['deadline_date'] ?? null,
            'is_freed' => $data['is_freed'] ?? false,
            'has_power_of_attorney' => $data['has_power_of_attorney'] ?? false,
            'contract_type_id' => $data['contract_type_id'] ?? null,
            'company_type_id' => $data['company_type_id'] ?? null
        ]);

        $accessibleUsers = $data['accessibleUsers'] ?? [];
        $contract->accessingUsers()->sync(collect($accessibleUsers));
        if (!in_array(Auth::id(), $accessibleUsers)) {
            $contract->accessingUsers()->attach(Auth::id());
        }

        $accessibleDepartments = $data['accessibleDepartments'] ?? [];
        $contract->accessingDepartments()->sync(collect($accessibleDepartments));

        $this->sendContractAddedNotifications($contract, $project, $originalName);

        $contract->save();

        if ($documentRequestId) {
            $this->handleDocumentRequest($documentRequestId, $contract);

            // Add the document requester to accessing users if not already present
            $documentRequest = DocumentRequest::find($documentRequestId);
            if ($documentRequest && $documentRequest->requester_id) {
                $contract->accessingUsers()->syncWithoutDetaching([$documentRequest->requester_id]);
            }
        }

        return $contract;
    }

    public function updateContract(Contract $contract, array $data, ?UploadedFile $file = null): Contract
    {
        $originalName = '';

        // Store previous user IDs before sync to determine newly added users
        $previousUserIds = $contract->accessingUsers()->pluck('users.id')->toArray();

        $accessibleUsers = $data['accessibleUsers'] ?? [];
        $contract->accessingUsers()->sync(collect($accessibleUsers));

        $accessibleDepartments = $data['accessibleDepartments'] ?? [];
        $contract->accessingDepartments()->sync(collect($accessibleDepartments));

        if ($file) {
            Storage::delete('contracts/' . $contract->basename);
            $this->handleFile(ArtworkFileTypes::CONTRACT, $file);
            $originalName = $file->getClientOriginalName();
            $basename = Str::random(20) . $originalName;

            $contract->basename = $basename;
            $contract->name = $originalName;

            Storage::putFileAs('contracts', $file, $basename);
        }

        $contract->fill($data);
        $contract->save();

        $project = $contract->project()->first();
        $this->sendContractUpdatedNotifications($contract, $project, $originalName, $previousUserIds);

        return $contract;
    }

    public function deleteContract(Contract $contract): void
    {
        $project = $contract->project()->first();
        $contractUsers = $contract->accessingUsers()->get();

        foreach ($contractUsers as $contractUser) {
            $notificationTitle = __('notification.contract.delete', [], $contractUser->language);
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => $contract->name,
                    'href' => null
                ],
                2 => [
                    'type' => 'link',
                    'title' => $project ? $project->name : '',
                    'href' => $project ? route(
                        'projects.tab',
                        [
                            $project->id,
                            $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                ProjectTabComponentEnum::BUDGET
                            )
                        ]
                    ) : null,
                ],
                3 => [
                    'type' => 'string',
                    'title' => $contract->contract_partner ? $contract->contract_partner : '',
                    'href' => null
                ],
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(2);
            $this->notificationService->setProjectId($project->id);
            $this->notificationService
                ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setNotificationTo($contractUser);
            $this->notificationService->createNotification();
        }

        $contract->forceDelete();
    }

    public function downloadContract(Contract $contract): StreamedResponse
    {
        return Storage::download('contracts/' . $contract->basename, $contract->name);
    }

    public function storeContractFile(Contract $contract, UploadedFile $file): void
    {
        if (!Storage::exists("contracts")) {
            Storage::makeDirectory("contracts");
        }

        $this->handleFile(ArtworkFileTypes::CONTRACT, $file);
        $originalName = $file->getClientOriginalName();
        $basename = Str::random(20) . $originalName;

        Storage::putFileAs('contracts', $file, $basename);

        $contract->basename = $basename;
        $contract->name = $originalName;
        $contract->save();
    }

    public function storeTasksAndComments(Model $contract, ?array $tasks = null, ?string $comment = null): void
    {
        $task = null;
        $taskObj = null;

        if ($tasks) {
            foreach ($tasks as $taskFromReq) {
                $taskObj = (object)$taskFromReq;
                if (isset($taskObj->new)) {
                    $task = Task::create([
                        'name' => $taskObj->name,
                        'description' => $taskObj->description,
                        'deadline' => $taskObj->deadline,
                        'done' => false,
                        'contract_id' => $contract->id,
                        'order' => 1
                    ]);
                } else {
                    Task::where('id', $taskObj->id)->update(['done' => $taskObj->done]);
                }
            }
        }

        if ($task && $taskObj && isset($taskObj->assigned_users)) {
            foreach ($taskObj->assigned_users as $assignedUser) {
                $userObj = (object)$assignedUser;
                $user = User::where('id', $userObj->id)->first();
                $task->task_users()->save($user);
                $user->tasks()->save($task);
            }
        }

        if ($comment) {
            $commentModel = Comment::create([
                'text' => $comment,
                'user_id' => Auth::id(),
                'project_file_id' => $contract->id
            ]);
            $contract->comments()->save($commentModel);
        }
    }

    public function exportContracts(array $filters, int $userId, string $language): BinaryFileResponse
    {
        $query = Contract::query()
            ->with(['project', 'contract_type', 'company_type', 'currency', 'creator']);

        if (!empty($filters['kskLiable'])) {
            $query->where('ksk_liable', true);
        }

        if (!empty($filters['foreignTax'])) {
            $query->where('foreign_tax', true);
        }

        if (!empty($filters['dateFrom'])) {
            $query->whereDate('deadline_date', '>=', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            $query->whereDate('deadline_date', '<=', $filters['dateTo']);
        }

        if (!empty($filters['legalFormIds']) && is_array($filters['legalFormIds']) && count($filters['legalFormIds']) > 0) {
            $query->whereIn('company_type_id', $filters['legalFormIds']);
        }

        if (!empty($filters['contractTypeIds']) && is_array($filters['contractTypeIds']) && count($filters['contractTypeIds']) > 0) {
            $query->whereIn('contract_type_id', $filters['contractTypeIds']);
        }

        $query->where(function ($q) use ($userId): void {
            $q->where('creator_id', $userId)
                ->orWhereHas('accessingUsers', function ($subQuery) use ($userId): void {
                    $subQuery->where('user_id', $userId);
                });
        });

        $contracts = $query->get();

        $export = new ContractExcelExport($contracts, $language);
        $filename = 'contracts_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return $export->download($filename)->deleteFileAfterSend();
    }

    public function saveUserFilter(int $userId, array $filters): void
    {
        UserContractFilter::updateOrCreate(
            ['user_id' => $userId],
            [
                'ksk_liable' => $filters['kskLiable'] ?? false,
                'foreign_tax' => $filters['foreignTax'] ?? false,
                'date_from' => $filters['dateFrom'] ?? null,
                'date_to' => $filters['dateTo'] ?? null,
                'legal_form_ids' => $filters['legalFormIds'] ?? [],
                'contract_type_ids' => $filters['contractTypeIds'] ?? [],
            ]
        );
    }

    private function sendContractAddedNotifications(Contract $contract, Project $project, string $originalName): void
    {
        $contractUsers = $contract->accessingUsers()->get();

        $this->notificationService->setIcon('green');
        $this->notificationService->setPriority(3);
        $this->notificationService->setProjectId($project->id);
        $this->notificationService
            ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED);

        foreach ($contractUsers as $contractUser) {
            $notificationTitle = __(
                'notification.contract.add',
                [],
                $contractUser->language
            );
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => $originalName,
                    'href' => null
                ],
                2 => [
                    'type' => 'link',
                    'title' => $project ? $project->name : '',
                    'href' => $project ? route(
                        'projects.tab',
                        [
                            $project->id,
                            $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                ProjectTabComponentEnum::BUDGET
                            )
                        ]
                    ) : null,
                ]
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setNotificationTo($contractUser);
            $this->notificationService->createNotification();
        }
    }

    private function sendContractUpdatedNotifications(Contract $contract, ?Project $project, string $originalName, array $previousUserIds = []): void
    {
        // Only notify users who are newly added (didn't have access before)
        $contractUsers = $contract->accessingUsers()->get()->filter(function ($user) use ($previousUserIds) {
            return !in_array($user->id, $previousUserIds);
        });

        if ($contractUsers->isEmpty()) {
            return;
        }

        $this->notificationService->setIcon('green');
        $this->notificationService->setPriority(3);
        if ($project) {
            $this->notificationService->setProjectId($project->id);
        }
        $this->notificationService
            ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED);

        foreach ($contractUsers as $contractUser) {
            $notificationTitle = __(
                'notification.contract.add',
                [],
                $contractUser->language
            );
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'green',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => $originalName,
                    'href' => null
                ],
                2 => [
                    'type' => 'link',
                    'title' => $project ? $project->name : '',
                    'href' => $project ? route(
                        'projects.tab',
                        [
                            $project->id,
                            $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                ProjectTabComponentEnum::BUDGET
                            )
                        ]
                    ) : null,
                ]
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setNotificationTo($contractUser);
            $this->notificationService->createNotification();
        }
    }

    private function handleDocumentRequest(int $documentRequestId, Contract $contract): void
    {
        $documentRequest = DocumentRequest::find($documentRequestId);
        if ($documentRequest) {
            $documentRequest->update([
                'contract_id' => $contract->id,
                'status' => DocumentRequest::STATUS_COMPLETED
            ]);

            $requester = $documentRequest->requester;
            if ($requester) {
                $uploader = Auth::user();
                $uploaderName = $uploader->first_name . ' ' . $uploader->last_name;

                $notificationTitle = __(
                    'notification.document_request.completed',
                    ['user' => $uploaderName],
                    $requester->language
                );

                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];

                $notificationDescription = [
                    1 => [
                        'type' => 'string',
                        'title' => __('notification.document_request.completed_description', [
                            'user' => $uploaderName
                        ], $requester->language),
                        'href' => null
                    ],
                    2 => [
                        'type' => 'link',
                        'title' => __('Document requests', [], $requester->language),
                        'href' => route('document-requests.index') . '?tab=completed',
                    ]
                ];

                $this->notificationService->setIcon('green');
                $this->notificationService->setPriority(3);
                $this->notificationService->setNotificationConstEnum(
                    NotificationEnum::NOTIFICATION_DOCUMENT_REQUEST_COMPLETED
                );
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setDescription($notificationDescription);
                $this->notificationService->setNotificationTo($requester);
                $this->notificationService->createNotification();
            }
        }
    }
}
