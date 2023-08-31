<?php

namespace App\Support\Services;

use Antonrom\ModelChangesHistory\Models\Change;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;

/**
 * Class NewHistoryService
 * @package App\Support\Services
 * @property string $modelObject
 * @property string $modelId
 * @property string $historyText
 * @property string $type
 * @property string $user
 * @property string $userObj
 * @property string $array
 * @property string $shift
 * @property string $event
 * @property string $eventTitle
 * @property string $shiftId
 * @property string $shiftDescription
 * @property string $change
 * @property string $changerType
 * @property string $changerId
 * @property string $stackTrace
 * @property string $created_at
 * @property string $updated_at
 */
class NewHistoryService
{
    /**
     * NewHistoryService constructor.
     * @param string $modelObject
     */
    public function __construct(protected string $modelObject){}


    protected int $modelId;
    protected string $historyText;
    protected string $type = 'project';

    public function getModelId(): int
    {
        return $this->modelId;
    }

    public function setModelId(int $modelId): void
    {
        $this->modelId = $modelId;
    }

    public function getHistoryText(): string
    {
        return $this->historyText;
    }

    public function setHistoryText(string $historyText): void
    {
        $this->historyText = $historyText;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }


    public function create(){
        $user = Auth::user();
        $userObj = [
            'id' => $user->id,
            'email' => $user->email,
            'business' => $user->business,
            'position' => $user->position,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'profile_photo_url' => $user->profile_photo_url,
            'profile_photo_path' => $user->profile_photo_path,
            'phone_number' => $user->phone_number,
            'description' => $user->description
        ];
        $array[] = ['type' => $this->getType(), 'message' => $this->getHistoryText(), 'changed_by' => $userObj];
        if($this->getType() === 'shift'){
            $shift = Shift::find($this->getModelId());
            $array[] = [
                'event_title' => $shift->event->eventName,
                'event_id' => $shift->event->id,
                'shift_id' => $shift->id,
                'shift_description' => $shift->description,
            ];
        }
        Change::create([
            'model_id' => $this->getModelId(),
            'model_type' => $this->modelObject,
            'changes' => json_encode($array),
            'change_type' => 'updated',
            'changer_type' => 'App\Models\User',
            'changer_id' => Auth::id(),
            'stack_trace' => null,
            'created_at' => now()
        ]);
    }


    /**
     * function to create history
     * @param int $modelId
     * @param string $historyText
     * @param string $type
     * @return void
     */
    public function createHistory(int $modelId, string $historyText, string $type = 'project'): void
    {
        $user = Auth::user();
        $userObj = [
            'id' => $user->id,
            'email' => $user->email,
            'business' => $user->business,
            'position' => $user->position,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'profile_photo_url' => $user->profile_photo_url,
            'profile_photo_path' => $user->profile_photo_path,
            'phone_number' => $user->phone_number,
            'description' => $user->description
        ];
        $array[] = ['type' => $type, 'message' => $historyText, 'changed_by' => $userObj];
        if($type === 'shift'){
            $shift = Shift::find($modelId);
            $array[] = [
                'event_title' => $shift->event->eventName,
                'event_id' => $shift->event->id,
                'shift_id' => $shift->id,
                'shift_description' => $shift->description,
            ];
        }
        Change::create([
            'model_id' => $modelId,
            'model_type' => $this->modelObject,
            'changes' => json_encode($array),
            'change_type' => 'updated',
            'changer_type' => 'App\Models\User',
            'changer_id' => Auth::id(),
            'stack_trace' => null,
            'created_at' => now()
        ]);
    }
}
