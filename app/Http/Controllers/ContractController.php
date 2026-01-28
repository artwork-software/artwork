<?php

namespace App\Http\Controllers;

use Artwork\Core\FileHandling\Upload\ArtworkFileTypes;
use Artwork\Core\FileHandling\Upload\HandlesFileUpload;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\CompanyType\Models\CompanyType;
use Artwork\Modules\Contract\Exports\ContractExcelExport;
use Artwork\Modules\Contract\Http\Requests\ContractUpdateRequest;
use Artwork\Modules\Contract\Http\Resources\ContractResource;
use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Contract\Http\Resources\ContractModuleResource;
use Artwork\Modules\Contract\Models\ContractModule;
use Artwork\Modules\Contract\Models\ContractType;
use Artwork\Modules\Currency\Models\Currency;
use Artwork\Modules\DocumentRequest\Models\DocumentRequest;
use Artwork\Modules\GeneralSettings\Services\GeneralSettingsService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContractFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Response;
use Inertia\ResponseFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractController extends Controller
{
    use HandlesFileUpload;

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ProjectTabService $projectTabService,
        private readonly ChangeService $changeService,
        private readonly GeneralSettingsService $generalSettingsService
    ) {
    }

    public function index(): Response|ResponseFactory
    {
        $user = Auth::user();
        // get all contracts where i am creator or i am accessing user
        $contracts = Contract::where('creator_id', $user->id)->get();
        $accessing_contracts = Contract::whereHas('accessingUsers', function ($query) use ($user): void {
            $query->where('user_id', $user->id);
        })->get();
        $contracts = $contracts->merge($accessing_contracts);

        // Load saved contract filter for current user
        $savedFilter = $user->contractFilter;

        return inertia('Contracts/ContractManagement', [
            'contracts' => ContractResource::collection($contracts)->resolve(),
            'contract_modules' => ContractModuleResource::collection(ContractModule::all()),
            'contract_types' => ContractType::all(),
            'company_types' => CompanyType::all(),
            'currencies' => Currency::all(),
            'first_project_tab_id' => $this->projectTabService->getFirstProjectTabId(),
            'first_project_calendar_tab_id' => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::CALENDAR),
            'saved_filter' => $savedFilter ? [
                'kskLiable' => $savedFilter->ksk_liable,
                'foreignTax' => $savedFilter->foreign_tax,
                'dateFrom' => $savedFilter->date_from,
                'dateTo' => $savedFilter->date_to,
                'legalFormIds' => $savedFilter->legal_form_ids ?? [],
                'contractTypeIds' => $savedFilter->contract_type_ids ?? [],
            ] : null,
        ]);
    }

    public function show(Contract $contract): Response|ResponseFactory
    {
        return inertia('Contracts/Contracts', [
            'contract' => new ContractResource($contract),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request, Project $project): RedirectResponse
    {
        if (!Storage::exists("contracts")) {
            Storage::makeDirectory("contracts");
        }
        $file = $request->file;
        $this->handleFile(ArtworkFileTypes::CONTRACT, $file);
        $original_name = $file->getClientOriginalName();
        $basename = Str::random(20) . $original_name;

        Storage::putFileAs('contracts', $file, $basename);

        $contract = $project->contracts()->create([
            'name' => $original_name,
            'basename' => $basename,
            'creator_id' => Auth::id(),
            'contract_partner' => $request->contract_partner,
            'amount' => $request->amount,
            'project_id' => $project->id,
            'currency_id' => $request->currency_id,
            'description' => $request->description,
            'ksk_liable' => $request->ksk_liable,
            'ksk_amount' => $request->ksk_amount,
            'ksk_reason' => $request->ksk_reason,
            'resident_abroad' => $request->resident_abroad,
            'foreign_tax' => $request->get('foreign_tax', false),
            'foreign_tax_amount' => $request->foreign_tax_amount,
            'foreign_tax_reason' => $request->foreign_tax_reason,
            'reverse_charge_amount' => $request->reverse_charge_amount,
            'deadline_date' => $request->deadline_date,
            'is_freed' => $request->get('is_freed', false),
            'has_power_of_attorney' => $request->get('has_power_of_attorney', false),
            'contract_type_id' => $request->contract_type_id,
            'company_type_id' => $request->company_type_id
        ]);

        $this->storeContractTasksAndComment($request, $contract);

        $contract->accessingUsers()->sync(collect($request->accessibleUsers));
        if (!in_array(Auth::id(), $request->accessibleUsers ?? [])) {
            $contract->accessingUsers()->attach(Auth::id());
        }

        $contractUsers =  $contract->accessingUsers()->get();

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
                    'title' => $original_name,
                    'href' => null
                ],
                2 => [
                    'type' => 'link',
                    'title' =>  $project ? $project->name : '',
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

        $contract->save();

        // Check if this upload is for a document request
        if ($request->document_request_id) {
            $documentRequest = DocumentRequest::find($request->document_request_id);
            if ($documentRequest) {
                // Link the contract to the document request and set status to completed
                $documentRequest->update([
                    'contract_id' => $contract->id,
                    'status' => DocumentRequest::STATUS_COMPLETED
                ]);

                // Send notification to the requester
                $requester = $documentRequest->requester;
                if ($requester) {
                    $uploader = Auth::user();
                    $uploaderName = $uploader->first_name . ' ' . $uploader->last_name;

                    $notificationTitle = __(
                        'notification.document_request.completed',
                        ['title' => $documentRequest->title, 'user' => $uploaderName],
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
                                'title' => $documentRequest->title,
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

        return Redirect::route('contracts.index');
    }

    public function download(Contract $contract): StreamedResponse
    {
        return Storage::download('contracts/' . $contract->basename, $contract->name);
    }

    public function update(Contract $contract, ContractUpdateRequest $request): RedirectResponse
    {
        $original_name = '';

        $contract->accessingUsers()->sync(collect($request->accessibleUsers));

        if ($request->file('file')) {
            Storage::delete('contracts/' . $contract->basename);
            $file = $request->file('file');
            $this->handleFile(ArtworkFileTypes::CONTRACT, $file);
            $original_name = $file->getClientOriginalName();
            $basename = Str::random(20) . $original_name;

            $contract->basename = $basename;
            $contract->name = $original_name;

            Storage::putFileAs('contracts', $file, $basename);
        }

        $contract->fill($request->data());

        $this->storeContractTasksAndComment($request, $contract);

        $contract->save();

        $project = $contract->project()->first();
        $contractUsers = $contract->accessingUsers()->get();


        $this->notificationService->setIcon('green');
        $this->notificationService->setPriority(3);
        $this->notificationService->setProjectId($project->id);
        $this->notificationService
            ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED);


        foreach ($contractUsers as $contractUser) {
            // notification.contract.add
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
                    'title' => $original_name,
                    'href' => null
                ],
                2 => [
                    'type' => 'link',
                    'title' =>  $project ? $project->name : '',
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
        return Redirect::back();
    }

    public function storeFile(Request $request): void
    {
        if (!Storage::exists("contracts")) {
            Storage::makeDirectory("contracts");
        }

        $file = $request->file;
        $this->handleFile(ArtworkFileTypes::CONTRACT, $file);
        $original_name = $file->getClientOriginalName();
        $basename = Str::random(20) . $original_name;

        Storage::putFileAs('contracts', $file, $basename);

        $contract = Contract::find($request->contract);
        $contract->basename = $basename;
        $contract->name = $original_name;
        $contract->save();
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $project = $contract->project()->first();
        $contractUsers =  $contract->accessingUsers()->get();

        foreach ($contractUsers as $contractUser) {
            // notification.contract.delete
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
                    'title' =>  $project ? $project->name : '',
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
        return Redirect::back();
    }

    public function storeContractTasksAndComment(Request $request, Model $contract): void
    {
        if (isset($request->tasks)) {
            foreach ($request->tasks as $task_from_req) {
                $task_obj = (object)$task_from_req;
                if (isset($task_obj->new)) {
                    $task = Task::create([
                        'name' => $task_obj->name,
                        'description' => $task_obj->description,
                        'deadline' => $task_obj->deadline,
                        'done' => false,
                        'contract_id' => $contract->id,
                        'order' => 1
                    ]);
                } else {
                    $task = Task::where('id', $task_obj->id)->update(['done' => $task_obj->done]);
                }
            }
        }

        if (isset($task_obj->assigned_users)) {
            foreach ($task_obj->assigned_users as $assigned_user) {
                $user_obj = (object)$assigned_user;
                $user = User::where('id', $user_obj->id)->first();
                $task->task_users()->save($user);
                $user->tasks()->save($task);
            }
        }

        if ($request->comment) {
            $comment = Comment::create([
                'text' => $request->comment,
                'user_id' => Auth::id(),
                'project_file_id' => $contract->id
            ]);
            $contract->comments()->save($comment);
        }
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $query = Contract::query()
            ->with(['project', 'contract_type', 'company_type', 'currency', 'creator']);

        // Filter by KSK-liable
        if ($request->boolean('kskLiable')) {
            $query->where('ksk_liable', true);
        }

        // Filter by foreign tax
        if ($request->boolean('foreignTax')) {
            $query->where('foreign_tax', true);
        }

        // Filter by date range (deadline_date)
        if ($request->filled('dateFrom')) {
            $query->whereDate('deadline_date', '>=', $request->input('dateFrom'));
        }
        if ($request->filled('dateTo')) {
            $query->whereDate('deadline_date', '<=', $request->input('dateTo'));
        }

        // Filter by legal forms (company_type_id)
        if ($request->filled('legalFormIds')) {
            $legalFormIds = $request->input('legalFormIds');
            if (is_array($legalFormIds) && count($legalFormIds) > 0) {
                $query->whereIn('company_type_id', $legalFormIds);
            }
        }

        // Filter by contract types
        if ($request->filled('contractTypeIds')) {
            $contractTypeIds = $request->input('contractTypeIds');
            if (is_array($contractTypeIds) && count($contractTypeIds) > 0) {
                $query->whereIn('contract_type_id', $contractTypeIds);
            }
        }

        // Only get contracts where user is creator or has access
        $userId = Auth::id();
        $query->where(function ($q) use ($userId): void {
            $q->where('creator_id', $userId)
                ->orWhereHas('accessingUsers', function ($subQuery) use ($userId): void {
                    $subQuery->where('user_id', $userId);
                });
        });

        $contracts = $query->get();
        $language = Auth::user()->language ?? 'de';

        $export = new ContractExcelExport($contracts, $language);
        $filename = 'contracts_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return $export->download($filename)->deleteFileAfterSend();
    }

    public function saveFilter(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();

        UserContractFilter::updateOrCreate(
            ['user_id' => $user->id],
            [
                'ksk_liable' => $request->boolean('kskLiable'),
                'foreign_tax' => $request->boolean('foreignTax'),
                'date_from' => $request->input('dateFrom'),
                'date_to' => $request->input('dateTo'),
                'legal_form_ids' => $request->input('legalFormIds', []),
                'contract_type_ids' => $request->input('contractTypeIds', []),
            ]
        );

        return response()->json(['success' => true]);
    }
}
