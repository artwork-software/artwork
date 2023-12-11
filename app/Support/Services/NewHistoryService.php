<?php

namespace App\Support\Services;

use Antonrom\ModelChangesHistory\Models\Change;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;

class NewHistoryService
{
    public function __construct(protected string $modelObject)
    {
    }

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

    public function create(): void
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
        $array[] = ['type' => $this->getType(), 'message' => $this->getHistoryText(), 'changed_by' => $userObj];
        if ($this->getType() === 'shift') {
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
        if ($type === 'shift') {
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
