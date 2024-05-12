<?php

namespace App\Http\Controllers;

use Artwork\Core\Http\Requests\SearchRequest;
use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\MainPositionDetails;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\SubPositionSumDetail;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\MoneySource\Http\Resources\MoneySourceFileResource;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Services\MoneySourceCalculationService;
use Artwork\Modules\MoneySourceCategory\Models\MoneySourceCategory;
use Artwork\Modules\MoneySourceReminder\Models\MoneySourceReminder;
use Artwork\Modules\MoneySourceTask\Models\MoneySourceTask;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\ProjectTab\Services\ProjectTabService;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;

class MoneySourceController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ChangeService $changeService
    ) {
    }

    public function index(MoneySourceCalculationService $moneySourceCalculationService): Response|ResponseFactory
    {
        $moneySources = MoneySource::with(['users', 'categories', 'moneySourceTasks'])->get();

        foreach ($moneySources as $moneySource) {
            $moneySource->sumOfPositions = $moneySourceCalculationService->getPositionSumOfOneMoneySource($moneySource);
            $historyArray = [];
            $historyComplete = $moneySource->historyChanges()->all();

            foreach ($historyComplete as $history) {
                $historyArray[] = [
                    'changes' => json_decode($history->changes),
                    'created_at' => $history->created_at->diffInHours() < 24
                        ? $history->created_at->diffForHumans()
                        : $history->created_at->format('d.m.Y, H:i'),
                ];
            }
            $moneySource->history = $historyArray;
        }

        return inertia('MoneySources/MoneySourceManagement', [
            'moneySourceCategories' => MoneySourceCategory::all(),
            'moneySources' => $moneySources,
            'moneySourceGroups' => MoneySource::where('is_group', true)->get(),
            //is set if index is called due to redirect response of store method
            'recentlyCreatedMoneySourceId' => session('recentlyCreatedMoneySourceId')
        ]);
    }

    public function showSettings(): Response|ResponseFactory
    {
        return inertia('MoneySources/MoneySourceSettings', [
            'moneySourceCategories' => MoneySourceCategory::all(),
        ]);
    }

    /**
     * @return MoneySource[]
     * @throws AuthorizationException
     */
    public function search(SearchRequest $request): array
    {
        $filteredObjects = [];
        $this->authorize('viewAny', User::class);
        if ($request->input('type') === 'single') {
            $moneySources = MoneySource::search($request->input('query'))->get();
            foreach ($moneySources as $moneySource) {
                if ($moneySource->is_group === 1 || $moneySource->is_group === true) {
                    continue;
                }
                $filteredObjects[] = $moneySource;
            }
            return $filteredObjects;
        } elseif ($request->input('type') === 'group') {
            $moneySources = MoneySource::search($request->input('query'))->get();
            foreach ($moneySources as $moneySource) {
                if ($moneySource->is_group === 1 || $moneySource->is_group === true) {
                    $filteredObjects[] = $moneySource;
                }
            }
            return $filteredObjects;
        } else {
            $moneySources = MoneySource::search($request->input('query'))->get();
            foreach ($moneySources as $moneySource) {
                if ($moneySource->projects->contains($request->projectId)) {
                    $filteredObjects[] = $moneySource;
                }
            }
            return $filteredObjects;
        }
    }

    public function create(): void
    {
    }

    public function store(Request $request): RedirectResponse
    {
        foreach ($request->users as $requestUser) {
            $user = User::find($requestUser['user_id']);
            $notificationTitle = __(
                'notification.moneySource.add_permission',
                [
                    'moneySourceName' => $request->name
                ],
                $user->language
            );
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $notificationTitle
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('green');
            $this->notificationService->setPriority(3);
            $this->notificationService->setNotificationConstEnum(
                NotificationEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED
            );
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setNotificationTo($user);
            $this->notificationService->createNotification();
        }

        if (!empty($request->amount)) {
            $amount = str_replace(',', '.', $request->amount);
        } else {
            $amount = 0.00;
        }

        $user = Auth::user();
        $moneySource = $user->money_sources()->create([
            'name' => $request->name,
            'amount' => $amount,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'funding_start_date' => $request->funding_start_date,
            'funding_end_date' => $request->funding_end_date,
            'source_name' => $request->source_name,
            'description' => $request->description,
            'is_group' => $request->is_group
        ]);

        $moneySource->users()->sync(collect($request->users));

        if ($request->is_group) {
            foreach ($request->sub_money_source_ids as $sub_money_source_id) {
                $money_source = MoneySource::find($sub_money_source_id);
                $money_source->update(['group_id' => $moneySource->id]);
            }
        }

        try {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(MoneySource::class)
                    ->setModelId($moneySource->id)
                    ->setTranslationKey('Money source created')
            );
        } catch (\Throwable $t) {
            dd($t->getMessage());
        }

        return back()->with(['recentlyCreatedMoneySourceId' => $moneySource->id]);
    }

    //@todo: fix phpcs error - refactor function because complexity exceeds allowed maximum
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
    public function show(MoneySource $moneySource, ProjectTabService $projectTabService): Response|ResponseFactory
    {
        $moneySource->load([
            'moneySourceFiles'
        ]);
        $amount = $moneySource->amount;
        $subMoneySources = MoneySource::where('group_id', $moneySource->id)->get();
        $columns = ColumnCell::where('linked_money_source_id', $moneySource->id)
            ->latest('column_id')
            ->get()
            ->unique('sub_position_row_id');

        $subPositionSumDetails = SubPositionSumDetail::with('subPosition.mainPosition.table.project', 'sumMoneySource')
            ->whereRelation('sumMoneySource', 'money_source_id', $moneySource->id)
            ->get();

        $mainPositionSumDetails = MainPositionDetails::with('mainPosition.table.project', 'sumMoneySource')
            ->whereRelation('sumMoneySource', 'money_source_id', $moneySource->id)
            ->get();

        $budgetSumDetails = BudgetSumDetails::with('column.table.project', 'sumMoneySource')
            ->whereRelation('sumMoneySource', 'money_source_id', $moneySource->id)
            ->get();

        $linked_projects = [];
        $positions = [];
        $subMoneySourcePositions = [];
        $usersWithAccess = [];
        if ($moneySource->is_group) {
            foreach ($subMoneySources as $subMoneySource) {
                $columns = ColumnCell::where('linked_money_source_id', $subMoneySource->id)
                    ->latest('column_id')
                    ->get()
                    ->unique('sub_position_row_id');
                foreach ($columns as $column) {
                    $subPositionRow = SubPositionRow::find($column->sub_position_row_id);
                    $subPosition = SubPosition::find($subPositionRow->sub_position_id);
                    $mainPosition = MainPosition::find($subPosition->main_position_id);
                    $table = Table::find($mainPosition->table_id);
                    $project = Project::where('id', $table->project_id)->with(['users'])->first();
                    foreach ($project->users as $user) {
                        if (!$user->pivot->is_manager) {
                            continue;
                        }
                        $usersWithAccess[] = $user->id;
                    }
                    $linked_projects[] = [
                        'id' => $project->id,
                        'name' => $project->name,
                    ];
                    $subMoneySourcePositions[] = [
                        'type' => $column->linked_type,
                        'value' => $column->value,
                        'subPositionName' => $subPosition->name,
                        'mainPositionName' => $mainPosition->name,
                        'project' => [
                            'id' => $project->id,
                            'name' => $project->name,
                        ],
                        'created_at' => date('d.m.Y', strtotime($column->created_at))
                    ];
                    if ($column->linked_type === 'EARNING') {
                        $amount = (int)$amount + (int)$column->value;
                    } else {
                        $amount = (int)$amount - (int)$column->value;
                    }
                }
            }
        } else {
            foreach ($budgetSumDetails as $detail) {
                foreach ($detail->column->table->costSums as $columnId => $costSum) {
                    if (
                        $columnId !== $detail->column_id ||
                        $detail->type !== 'COST'
                    ) {
                        continue;
                    }
                    $positions[] = [
                        'type' => $detail->sumMoneySource->linked_type,
                        'value' => $costSum,
                        'subPositionName' => "",
                        'mainPositionName' => "",
                        'project' => [
                            'id' => $detail->column->table->project->id,
                            'name' => $detail->column->table->project->name,
                        ],
                        'created_at' => date('d.m.Y', strtotime($detail->created_at))
                    ];
                    if ($detail->sumMoneySource->linked_type === 'EARNING') {
                        $amount = (int)$amount + (int)$costSum;
                    } else {
                        $amount = (int)$amount - (int)$costSum;
                    }
                }

                foreach ($detail->column->table->earningSums as $columnId => $earningSum) {
                    if (
                        $columnId !== $detail->column_id ||
                        $detail->type !== 'EARNING'
                    ) {
                        continue;
                    }
                    $positions[] = [
                        'type' => $detail->sumMoneySource->linked_type,
                        'value' => $earningSum,
                        'subPositionName' => "",
                        'mainPositionName' => "",
                        'project' => [
                            'id' => $detail->column->table->project->id,
                            'name' => $detail->column->table->project->name,
                        ],
                        'created_at' => date('d.m.Y', strtotime($detail->created_at))
                    ];
                    if ($detail->sumMoneySource->linked_type === 'EARNING') {
                        $amount = (int)$amount + (int)$earningSum;
                    } else {
                        $amount = (int)$amount - (int)$earningSum;
                    }
                }
            }

            foreach ($subPositionSumDetails as $detail) {
                foreach ($detail->subPosition->columnSums as $columnId => $columnSum) {
                    if ($columnId !== $detail->column_id) {
                        continue;
                    }
                    $positions[] = [
                        'type' => $detail->sumMoneySource->linked_type,
                        'value' => $columnSum['sum'],
                        'subPositionName' => 'Summe von ' . $detail->subPosition->name,
                        'mainPositionName' => $detail->subPosition->mainPosition->name,
                        'project' => [
                            'id' => $detail->subPosition->mainPosition->table->project->id,
                            'name' => $detail->subPosition->mainPosition->table->project->name,
                        ],
                        'is_sum' => true,
                        'created_at' => date('d.m.Y', strtotime($detail->created_at))
                    ];
                    if ($detail->sumMoneySource->linked_type === 'EARNING') {
                        $amount = (int)$amount + (int)$columnSum['sum'];
                    } else {
                        $amount = (int)$amount - (int)$columnSum['sum'];
                    }
                }
            }

            foreach ($mainPositionSumDetails as $detail) {
                foreach ($detail->mainPosition->columnSums as $columnId => $columnSum) {
                    if ($columnId !== $detail->column_id) {
                        continue;
                    }
                    $positions[] = [
                        'type' => $detail->sumMoneySource->linked_type,
                        'value' => $columnSum['sum'],
                        'subPositionName' => "",
                        'mainPositionName' => "Summe von " . $detail->mainPosition->name,
                        'project' => [
                            'id' => $detail->mainPosition->table->project->id,
                            'name' => $detail->mainPosition->table->project->name,
                        ],
                        'is_sum' => true,
                        'created_at' => date('d.m.Y', strtotime($detail->created_at))
                    ];
                    if ($detail->sumMoneySource->linked_type === 'EARNING') {
                        $amount = (int)$amount + (int)$columnSum['sum'];
                    } else {
                        $amount = (int)$amount - (int)$columnSum['sum'];
                    }
                }
            }

            foreach ($columns as $column) {
                $subPositionRow = SubPositionRow::find($column->sub_position_row_id);
                $subPosition = SubPosition::find($subPositionRow->sub_position_id);
                $mainPosition = MainPosition::find($subPosition->main_position_id);
                $table = Table::find($mainPosition->table_id);

                $project = Project::where('id', $table->project_id)->with(['users'])->first();
                foreach ($project->users as $user) {
                    if (!$user->pivot->is_manager) {
                        continue;
                    }
                    $usersWithAccess[] = $user->id;
                }
                $linked_projects[] = [
                    'id' => $project->id,
                    'name' => $project->name,
                ];
                $positions[] = [
                    'type' => $column->linked_type,
                    'value' => $column->value,
                    'subPositionName' => $subPosition->name,
                    'mainPositionName' => $mainPosition->name,
                    'project' => [
                        'id' => $project->id,
                        'name' => $project->name,
                    ],
                    'column_name' => $column->column->name,
                    'created_at' => date('d.m.Y', strtotime($column->created_at))
                ];

                if ($column->linked_type === 'EARNING') {
                    $amount = (int)$amount + (int)$column->value;
                } else {
                    $amount = (int)$amount - (int)$column->value;
                }
            }
        }

        $historyArray = [];
        $historyComplete = $moneySource->historyChanges()->all();

        foreach ($historyComplete as $history) {
            $historyArray[] = [
                'changes' => json_decode($history->changes),
                'created_at' => $history->created_at->diffInHours() < 24
                    ? $history->created_at->diffForHumans()
                    : $history->created_at->format('d.m.Y, H:i'),
            ];
        }

        return inertia('MoneySources/Show', [
            'moneySource' => [
                'id' => $moneySource->id,
                'creator' => User::find($moneySource->creator_id),
                'name' => $moneySource->name,
                'amount' => $moneySource->amount,
                'amount_available' => $amount,
                'source_name' => $moneySource->source_name,
                'start_date' => $moneySource->start_date,
                'end_date' => $moneySource->end_date,
                'funding_start_date' => $moneySource->funding_start_date,
                'funding_end_date' => $moneySource->funding_end_date,
                'users' => $moneySource->users()->get(),
                'group_id' => $moneySource->group_id,
                'money_source_files' => MoneySourceFileResource::collection($moneySource->moneySourceFiles),
                'moneySourceGroup' => MoneySource::find($moneySource->group_id),
                'subMoneySources' => $subMoneySources->map(fn($source) => [
                    'id' => $source->id,
                    'name' => $source->name,
                ]),
                'description' => $moneySource->description,
                'is_group' => $moneySource->is_group,
                'created_at' => $moneySource->created_at,
                'updated_at' => $moneySource->updated_at,
                'tasks' => MoneySourceTask::with('money_source_task_users')
                    ->where('money_source_id', $moneySource->id)
                    ->get()
                    ->map(
                        fn($task) => [
                            'id' => $task->id,
                            'money_source_id' => $task->money_source_id,
                            'name' => $task->name,
                            'description' => $task->description,
                            'deadline' => $task->deadline,
                            'done' => (bool)$task->done,
                            'money_source_task_users' => $task->money_source_task_users,
                            'pivot' => $task->pivot
                        ]
                    ),
                'positions' => $positions,
                'subMoneySourcePositions' => $subMoneySourcePositions,
                'linked_projects' => array_unique($linked_projects, SORT_REGULAR),
                'usersWithAccess' => array_unique($usersWithAccess, SORT_NUMERIC),
                'history' => $historyArray,
                'categories' => $moneySource->categories,
                'hasSentExpirationReminderNotification' => $moneySource->reminder()
                    ->where('type', '=', MoneySourceReminder::MONEY_SOURCE_REMINDER_TYPE_EXPIRATION)
                    ->where('notification_created', '=', true)
                    ->count() > 0,
                'hasSentThresholdReminderNotification' => $moneySource->reminder()
                    ->where('type', '=', MoneySourceReminder::MONEY_SOURCE_REMINDER_TYPE_THRESHOLD)
                    ->where('notification_created', '=', true)
                    ->count() > 0
            ],
            'moneySourceGroups' => MoneySource::where('is_group', true)->get(),
            'moneySourceCategories' => MoneySourceCategory::all(),
            'moneySources' => MoneySource::where('is_group', false)->get(),
            'projects' => Project::all()->map(fn($project) => [
                'id' => $project->id,
                'name' => $project->name,
            ]),
            'linkedProjects' => $moneySource->projects()->get(),
            'first_project_budget_tab_id' => $projectTabService->findFirstProjectTabWithBudgetComponent()?->id
        ]);
    }

    public function edit(): void
    {
    }

    public function update(Request $request, MoneySource $moneySource): void
    {
        $oldName = $moneySource->name;
        $oldDescription = $moneySource->description;

        $oldUsers = $moneySource->users()->get();
        $oldAmount = $moneySource->amount;

        if (!empty($request->amount)) {
            $amount = str_replace(',', '.', $request->amount);
        } else {
            $amount = 0.00;
        }

        $beforeSubMoneySources = MoneySource::where('group_id', $moneySource->id)->get();
        foreach ($beforeSubMoneySources as $beforeSubMoneySource) {
            $beforeSubMoneySource->update(['group_id' => null]);
        }

        $moneySource->users()->sync(collect($request->users));

        $moneySource->update([
            'name' => $request->name,
            'amount' => $amount,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'source_name' => $request->source_name,
            'description' => $request->description,
            'is_group' => $request->is_group,
            'group_id' => $request->group_id,
            'funding_start_date' => $request->funding_start_date,
            'funding_end_date' => $request->funding_end_date,
        ]);

        $newName = $moneySource->name;
        $newDescription = $moneySource->description;

        $newUsers = $moneySource->users()->get();
        $this->checkUserChanges($moneySource, $oldUsers, $newUsers);

        $newAmount = $moneySource->amount;

        if ($request->is_group) {
            foreach ($request->sub_money_source_ids as $sub_money_source_id) {
                $money_source = MoneySource::find($sub_money_source_id);
                $money_source->update(['group_id' => $moneySource->id]);
            }
        }

        if ($oldName !== $newName) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(MoneySource::class)
                    ->setModelId($moneySource->id)
                    ->setTranslationKey('Money source name changed')
            );
        }

        if ($oldDescription !== $newDescription && !empty($newDescription) && !empty($oldDescription)) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(MoneySource::class)
                    ->setModelId($moneySource->id)
                    ->setTranslationKey('Description changed')
            );
        }

        if (empty($oldDescription) && !empty($newDescription)) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(MoneySource::class)
                    ->setModelId($moneySource->id)
                    ->setTranslationKey('Description added')
            );
        }

        if (!empty($oldDescription) && empty($newDescription)) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(MoneySource::class)
                    ->setModelId($moneySource->id)
                    ->setTranslationKey('Description deleted')
            );
        }

        if (floatval($oldAmount) !== floatval($newAmount)) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(MoneySource::class)
                    ->setModelId($moneySource->id)
                    ->setTranslationKey('Changed original volume')
            );
        }
    }

    public function updateUsers(Request $request, MoneySource $moneySource): void
    {
        $moneySource->users()->sync(collect($request->users));
        $tasks = $moneySource->moneySourceTasks()->get();
        foreach ($tasks as $task) {
            $task->money_source_task_users()->sync($moneySource->competent()->get());
        }
    }

    public function destroy(MoneySource $moneySource): RedirectResponse
    {
        $beforeSubMoneySources = MoneySource::where('group_id', $moneySource->id)->get();
        foreach ($beforeSubMoneySources as $beforeSubMoneySource) {
            $beforeSubMoneySource->update(['group_id' => null]);
        }
        $users = $moneySource->users()->get();
        if ($users) {
            foreach ($users as $user) {
                $notificationTitle = __(
                    'notification.moneySource.deleted',
                    ['moneySource' => $moneySource->name],
                    $user->language
                );
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('red');
                $this->notificationService->setPriority(2);
                $this->notificationService->setNotificationConstEnum(
                    NotificationEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED
                );
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setNotificationTo(User::find($user->id));
                $this->notificationService->createNotification();
            }
        }

        $cells = ColumnCell::where('linked_money_source_id', $moneySource->id)->get();

        $cells->each(function ($cell): void {
            $cell->update(['linked_money_source_id' => null]);
        });

        $moneySource->delete();
        return Redirect::route('money_sources.index');
    }

    public function duplicate(MoneySource $moneySource): RedirectResponse
    {
        $user = Auth::user();
        $newMoneySource = $user->money_sources()->create([
            'name' => '(Kopie) ' . $moneySource->name,
            'amount' => $moneySource->amount,
            'start_date' => $moneySource->start_date,
            'end_date' => $moneySource->end_date,
            'source_name' => $moneySource->source_name,
            'description' => $moneySource->description,
            'is_group' => $moneySource->is_group,
            'group_id' => $moneySource->group_id,
            'users' => $moneySource->users
        ]);

        return Redirect::route('money_sources.index');
    }

    public function pin(MoneySource $moneySource): \Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();
        $pinnedByUsers = $moneySource->pinned_by_users;

        if (is_null($pinnedByUsers)) {
            $pinnedByUsers = [];
        }
        if (in_array($user->id, $pinnedByUsers)) {
            $pinnedByUsers = array_diff($pinnedByUsers, [$user->id]);
        } else {
            $pinnedByUsers[] = $user->id;
        }
        $moneySource->update(['pinned_by_users' => $pinnedByUsers]);
        return Redirect::route('money_sources.index');
    }

    private function checkUserChanges($moneySource, $oldUsers, $newUsers): void
    {
        $oldUserIds = [];
        $newUserIds = [];

        foreach ($oldUsers as $oldUser) {
            $oldUserIds[] = $oldUser->id;
        }

        foreach ($newUsers as $newUser) {
            $newUserIds[] = $newUser->id;
            if (!in_array($newUser->id, $oldUserIds)) {
                $user = User::find($newUser->id);
                $notificationTitle = __(
                    'notification.moneySource.add_permission',
                    ['moneySource' => $moneySource->name],
                    $user->language
                );
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('green');
                $this->notificationService->setPriority(3);
                $this->notificationService->setNotificationConstEnum(
                    NotificationEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED
                );
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setNotificationTo($user);
                $this->notificationService->createNotification();

                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setModelClass(MoneySource::class)
                        ->setModelId($moneySource->id)
                        ->setTranslationKey('User access to money source added')
                );
            }
        }

        foreach ($oldUserIds as $oldUserId) {
            if (!in_array($oldUserId, $newUserIds)) {
                $user = User::find($newUser->id);
                $notificationTitle = __(
                    'notification.moneySource.remove_permission',
                    ['moneySource' => $moneySource->name],
                    $user->language
                );
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'error',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('red');
                $this->notificationService->setPriority(2);
                $this->notificationService->setNotificationConstEnum(
                    NotificationEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED
                );
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setNotificationTo($user);
                $this->notificationService->createNotification();

                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setModelClass(MoneySource::class)
                        ->setModelId($moneySource->id)
                        ->setTranslationKey('User access to money source removed')
                );
            }
        }
    }

    public function updateProjects(MoneySource $moneySource, Request $request): void
    {
        $moneySource->projects()->sync($request->linkedProjectIds);
    }

    public function syncCategories(MoneySource $moneySource, Request $request): void
    {
        $moneySource->categories()->sync($request->categoryIds);
    }
}
