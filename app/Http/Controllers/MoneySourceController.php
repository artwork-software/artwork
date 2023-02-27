<?php

namespace App\Http\Controllers;

use App\Enums\NotificationConstEnum;
use App\Http\Requests\SearchRequest;
use App\Http\Resources\MoneySourceFileResource;
use App\Models\ColumnCell;
use App\Models\MainPosition;
use App\Models\MoneySource;
use App\Models\MoneySourceTask;
use App\Models\Project;
use App\Models\SubPosition;
use App\Models\SubPositionRow;
use App\Models\Table;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use stdClass;

class MoneySourceController extends Controller
{
    protected ?NotificationController $notificationController = null;
    protected ?stdClass $notificationData = null;
    protected ?HistoryController $history = null;

    public function __construct()
    {
        $this->notificationController = new NotificationController();
        $this->notificationData = new \stdClass();
        $this->history = new HistoryController('App\Models\MoneySource');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        return inertia('MoneySources/MoneySourceManagement', [
            'moneySources' => MoneySource::all(),
            'moneySourceGroups' => MoneySource::where('is_group', true)->get(),
        ]);
    }

    public function search(SearchRequest $request)
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

        } else if ($request->input('type') === 'group') {
            $moneySources = MoneySource::search($request->input('query'))->get();
            foreach ($moneySources as $moneySource) {
                if ($moneySource->is_group === 1 || $moneySource->is_group === true) {
                    $filteredObjects[] = $moneySource;
                }
            }
            return $filteredObjects;
        } else {
            $moneySources = MoneySource::search($request->input('query'))->get();
            foreach ($moneySources as $moneySource){
                if($moneySource->projects->contains($request->projectId)){
                    $filteredObjects[] = $moneySource;
                }
            }
            return $filteredObjects;
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $inputArray = [];

        foreach ($request->users as $requestUser){
            $user = User::find($requestUser);
            $inputArray[] = $user;
            // create user Notification
            $this->notificationData->type = NotificationConstEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED;
            $this->notificationData->title = 'Du hast Zugriff auf "'. $request->name . '" erhalten';
            $this->notificationData->created_by = Auth::user();
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $this->notificationData->title
            ];
            $this->notificationController->create($user, $this->notificationData, $broadcastMessage);

        }

        if (!empty($request->amount)) {
            $amount = str_replace(',', '.', $request->amount);
        } else {
            $amount = 0.00;
        }

        $user = Auth::user();
        $source = $user->money_sources()->create([
            'name' => $request->name,
            'amount' => $amount,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'source_name' => $request->source_name,
            'description' => $request->description,
            'is_group' => $request->is_group,
            'users' => json_encode($inputArray)
        ]);

        if ($request->is_group) {
            foreach ($request->sub_money_source_ids as $sub_money_source_id) {
                $money_source = MoneySource::find($sub_money_source_id);
                $money_source->update(['group_id' => $source->id]);
            }
        }

        $this->history->createHistory($source->id, 'Finanzierungsquelle erstellt');

        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\MoneySource $moneySource
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(MoneySource $moneySource)
    {
        $moneySource->load([
            'money_source_files'
            ]);
        $amount = $moneySource->amount;
        $subMoneySources = MoneySource::where('group_id', $moneySource->id)->get();
        $columns = ColumnCell::where('linked_money_source_id', $moneySource->id)->get();
        $linked_projects = [];
        $positions = [];
        $subMoneySourcePositions = [];
        $usersWithAccess = [];
        if($moneySource->is_group){
            foreach ($subMoneySources as $subMoneySource){
                $columns = ColumnCell::where('linked_money_source_id', $subMoneySource->id)->get();
                foreach ($columns as $column) {
                    $subPositionRow = SubPositionRow::find($column->sub_position_row_id);
                    $subPosition = SubPosition::find($subPositionRow->sub_position_id);
                    $mainPosition = MainPosition::find($subPosition->main_position_id);
                    $table = Table::find($mainPosition->table_id);
                    $project = Project::where('id',$table->project_id)->with(['users'])->first();
                    foreach ($project->users as $user){
                        if(!$user->pivot->is_manager){
                            continue;
                        }
                        $usersWithAccess[] = $user->id;
                    }
                        $linked_projects[] = [
                            'id' =>$project->id,
                            'name' => $project->name,
                        ];
                    $subMoneySourcePositions[] = [
                        'type' => $column->linked_type,
                        'value' => $column->value,
                        'subPositionName' => $subPosition->name,
                        'mainPositionName' => $mainPosition->name,
                        'project' => [
                            'id' =>$project->id,
                            'name' => $project->name,
                        ],
                        'created_at' => date('d.m.Y', strtotime($column->created_at))
                    ];
                    if($column->linked_type === 'EARNING'){
                        $amount = (int)$amount + (int)$column->value;
                    } else {
                        $amount = (int)$amount - (int)$column->value;
                    }
                }
            }
        }else{
            foreach ($columns as $column) {
                $subPositionRow = SubPositionRow::find($column->sub_position_row_id);
                $subPosition = SubPosition::find($subPositionRow->sub_position_id);
                $mainPosition = MainPosition::find($subPosition->main_position_id);
                $table = Table::find($mainPosition->table_id);

                $project = Project::where('id',$table->project_id)->with(['users'])->first();
                foreach ($project->users as $user){
                    if(!$user->pivot->is_manager){
                        continue;
                    }
                    $usersWithAccess[] = $user->id;
                }
                $linked_projects[] = [
                        'id' =>$project->id,
                        'name' => $project->name,
                    ];
                $positions[] = [
                    'type' => $column->linked_type,
                    'value' => $column->value,
                    'subPositionName' => $subPosition->name,
                    'mainPositionName' => $mainPosition->name,
                    'project' => [
                        'id' =>$project->id,
                        'name' => $project->name,
                    ],
                    'created_at' => date('d.m.Y', strtotime($column->created_at))
                ];
                if($column->linked_type === 'EARNING'){
                    $amount = (int)$amount + (int)$column->value;
                } else {
                    $amount = (int)$amount - (int)$column->value;
                }
            }
        }

        $historyArray = [];
        $historyComplete = $moneySource->historyChanges()->all();

        foreach ($historyComplete as $history){
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
                'users' => json_decode($moneySource->users),
                'group_id' => $moneySource->group_id,
                'money_source_files' => MoneySourceFileResource::collection($moneySource->money_source_files),
                'moneySourceGroup' => MoneySource::find($moneySource->group_id),
                'subMoneySources' => $subMoneySources->map(fn ($source) => [
                    'id' => $source->id,
                    'name' => $source->name,
                ]),
                'description' => $moneySource->description,
                'is_group' => $moneySource->is_group,
                'created_at' => $moneySource->created_at,
                'updated_at' => $moneySource->updated_at,
                'tasks' => MoneySourceTask::with('money_source_task_users')->where('money_source_id', $moneySource->id)->get()->map(fn ($task) => [
                    'id' => $task->id,
                    'money_source_id' => $task->money_source_id,
                    'name' => $task->name,
                    'description' => $task->description,
                    'deadline' => $task->deadline,
                    'done' => (bool) $task->done,
                    'money_source_task_users' => $task->money_source_task_users,
                    'pivot' => $task->pivot
                    ]),
                'positions' => $positions,
                'subMoneySourcePositions' => $subMoneySourcePositions,
                'linked_projects' => array_unique($linked_projects,SORT_REGULAR),
                'usersWithAccess' => array_unique($usersWithAccess,SORT_NUMERIC),
                'history' => $historyArray
            ],
            'moneySourceGroups' => MoneySource::where('is_group', true)->get(),
            'moneySources' => MoneySource::where('is_group', false)->get(),
            'projects' => Project::all()->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
            ]),
            'linkedProjects' => $moneySource->projects()->get()
        ]);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\MoneySource $moneySource
     * @return \Illuminate\Http\Response
     */
    public function edit(MoneySource $moneySource)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\MoneySource $moneySource
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MoneySource $moneySource)
    {
        $oldMoneySourceUsers = json_decode($moneySource->users);
        $inputArray = [];

        $oldName = $moneySource->name;
        $oldDescription = $moneySource->description;

        foreach ($request->users as $requestUser) {
            $user = User::find($requestUser);
            $inputArray[] = $user;
        }

        if (!empty($request->amount)) {
            $amount = str_replace(',', '.', $request->amount);
        } else {
            $amount = 0.00;
        }

        $beforeSubMoneySources = MoneySource::where('group_id', $moneySource->id)->get();
        foreach ($beforeSubMoneySources as $beforeSubMoneySource) {
            $beforeSubMoneySource->update(['group_id' => null]);
        }

        $moneySource->update([
            'name' => $request->name,
            'amount' => $amount,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'source_name' => $request->source_name,
            'description' => $request->description,
            'is_group' => $request->is_group,
            'group_id' => $request->group_id,
            'users' => json_encode($inputArray)
        ]);

        $newName = $moneySource->name;
        $newDescription = $moneySource->description;

        if($oldName !== $newName){
            $this->history->createHistory($moneySource->id, 'Finanzierungsquellenname geändert');
        }

        if($oldDescription !== $newDescription && !empty($newDescription) && !empty($oldDescription)){
            $this->history->createHistory($moneySource->id, 'Beschreibung geändert');
        }

        if(empty($oldDescription) && !empty($newDescription)){
            $this->history->createHistory($moneySource->id, 'Beschreibung hinzugefügt');
        }
        if(!empty($oldDescription) && empty($newDescription)){
            $this->history->createHistory($moneySource->id, 'Beschreibung gelöscht');
        }

        if ($request->is_group) {
            foreach ($request->sub_money_source_ids as $sub_money_source_id) {
                $money_source = MoneySource::find($sub_money_source_id);
                $money_source->update(['group_id' => $moneySource->id]);
            }
        }

        $newMoneySourceUsers = json_decode($moneySource->users);

        $this->checkUserChanges($moneySource, $oldMoneySourceUsers, $newMoneySourceUsers);
    }

    public function updateUsers(Request $request, MoneySource $moneySource){

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\MoneySource $moneySource
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(MoneySource $moneySource): \Illuminate\Http\RedirectResponse
    {
        $beforeSubMoneySources = MoneySource::where('group_id', $moneySource->id)->get();
        foreach ($beforeSubMoneySources as $beforeSubMoneySource) {
            $beforeSubMoneySource->update(['group_id' => null]);
        }
        $users = json_decode($moneySource->users);
        foreach ($users as $user){
            $this->notificationData->type = NotificationConstEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED;
            $this->notificationData->title = 'Finanzierungsquelle/gruppe ' .$moneySource->name . ' wurde gelöscht';
            $this->notificationData->created_by = Auth::user();
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $this->notificationData->title
            ];
            $this->notificationController->create(User::find($user->id), $this->notificationData, $broadcastMessage);
        }
        $moneySource->delete();
        return Redirect::route('money_sources.index')->with('success', 'MoneySource deleted.');
    }

    public function duplicate(MoneySource $moneySource): \Illuminate\Http\RedirectResponse
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

        return Redirect::route('money_sources.index')->with('success', 'MoneySource duplicated.');
    }


    private function checkUserChanges($moneySource, $oldUsers, $newUsers){
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED;
        $oldUserIds = [];
        $newUserIds = [];

        foreach ($oldUsers as $oldUser){
            $oldUserIds[] = $oldUser->id;
        }

        foreach ($newUsers as $newUser){
            $newUserIds[] = $newUser->id;
            if(!in_array($newUser->id, $oldUserIds)){
                $this->notificationData->title = 'Du hast Zugriff auf ' . $moneySource->name . ' erhalten';
                $this->notificationData->created_by = Auth::user();
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $this->notificationData->title
                ];
                $this->notificationController->create(User::where('id', $newUser->id)->first(), $this->notificationData, $broadcastMessage);
                $this->history->createHistory($moneySource->id, 'Nutzerzugriff zu Finanzierungsquelle hinzugefügt');
            }
        }

        foreach ($oldUserIds as $oldUserId){
            if(!in_array($oldUserId, $newUserIds)){
                $this->notificationData->title = 'Dein Zugriff auf ' . $moneySource->name . ' wurde gelöscht';
                $this->notificationData->created_by = Auth::user();
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $this->notificationData->title
                ];
                $this->notificationController->create(User::where('id', $oldUserId)->first(), $this->notificationData, $broadcastMessage);
                $this->history->createHistory($moneySource->id, 'Nutzerzugriff zu Finanzierungsquelle entfernt');
            }

        }
    }
    public function updateProjects(MoneySource $moneySource,Request $request)
    {
        $moneySource->projects()->sync($request->linkedProjectIds);
    }

}
