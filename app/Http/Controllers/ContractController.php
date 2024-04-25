<?php

namespace App\Http\Controllers;

use Artwork\Modules\CompanyType\Models\CompanyType;
use Artwork\Modules\Contract\Http\Requests\ContractUpdateRequest;
use Artwork\Modules\Contract\Http\Resources\ContractResource;
use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\ContractModule\Http\Resources\ContractModuleResource;
use Artwork\Modules\ContractModule\Models\ContractModule;
use Artwork\Modules\ContractType\Models\ContractType;
use Artwork\Modules\Currency\Models\Currency;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\ProjectTab\Services\ProjectTabService;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Response;
use Inertia\ResponseFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ProjectTabService $projectTabService
    ) {
    }

    public function index(): Response|ResponseFactory
    {
        // get all contracts where i am creator or i am accessing user
        $contracts = Contract::where('creator_id', Auth::id())->get();
        $accessing_contracts = Contract::whereHas('accessingUsers', function ($query): void {
            $query->where('user_id', Auth::id());
        })->get();
        $contracts = $contracts->merge($accessing_contracts);
        //dd(ContractResource::collection($contracts)->resolve());
        return inertia('Contracts/ContractManagement', [
            'contracts' => ContractResource::collection($contracts)->resolve(),
            'contract_modules' => ContractModuleResource::collection(ContractModule::all()),
            'contract_types' => ContractType::all(),
            'company_types' => CompanyType::all(),
            'currencies' => Currency::all(),
            'first_project_tab_id' => $this->projectTabService->findFirstProjectTab()?->id,
            'first_project_calendar_tab_id' => $this->projectTabService->findFirstProjectTabWithCalendarComponent()?->id
        ]);
    }

    public function show(Contract $contract): Response|ResponseFactory
    {
        return inertia('Contracts/Contracts', [
            'contract' => new ContractResource($contract),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        if (!Storage::exists("contracts")) {
            Storage::makeDirectory("contracts");
        }
        $file = $request->file;
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
            'resident_abroad' => $request->resident_abroad,
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
                            $this->projectTabService->findFirstProjectTabWithBudgetComponent()?->id
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
                            $this->projectTabService->findFirstProjectTabWithBudgetComponent()?->id
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
                            $this->projectTabService->findFirstProjectTabWithBudgetComponent()?->id
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
                    //dd($task_obj);
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
}
