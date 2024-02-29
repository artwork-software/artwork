<?php

namespace App\Support\Services;

use Antonrom\ModelChangesHistory\Models\Change;
use App\Models\User;
use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Support\Facades\Auth;

class NewHistoryService
{
    private int $modelId;

    private string $translationKey;

    private array $translationKeyPlaceholderValues = [];

    private string $type = 'project';

    private mixed $modelObject;

    public function __construct($modelObject = null)
    {
        if ($modelObject) {
            $this->setModel($modelObject);
        }
    }

    public function setModel(mixed $model): void
    {
        if (is_object($model)) {
            $this->modelObject = $model::class;
            return;
        }

        $this->modelObject = (string)$model;
    }

    public function getModelId(): int
    {
        return $this->modelId;
    }

    public function setModelId(int $modelId): void
    {
        $this->modelId = $modelId;
    }

    public function getTranslationKey(): string
    {
        return $this->translationKey;
    }

    public function setTranslationKey(string $translationKey): void
    {
        $this->translationKey = $translationKey;
    }

    /**
     * @return string[]
     */
    public function getTranslationKeyPlaceholderValues(): array
    {
        return $this->translationKeyPlaceholderValues;
    }

    public function setTranslationKeyPlaceholderValues(array $translationKeyPlaceholderValues): void
    {
        $this->translationKeyPlaceholderValues = $translationKeyPlaceholderValues;
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
        $array[] = [
            'type' => $this->getType(),
            'translationKey' => $this->getTranslationKey(),
            'translationKeyPlaceholderValues' => $this->getTranslationKeyPlaceholderValues(),
            'changed_by' => $userObj
        ];
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
            'changer_type' => User::class,
            'changer_id' => Auth::id(),
            'stack_trace' => null,
            'created_at' => now()
        ]);
    }

    public function createHistory(
        int $modelId,
        string $translationKey,
        array $translationKeyPlaceholderValues = [],
        string $type = 'project'
    ): void {
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
        $array[] = [
            'type' => $type,
            'translationKey' => $translationKey,
            'translationKeyPlaceholderValues' => $translationKeyPlaceholderValues,
            'changed_by' => $userObj
        ];
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
