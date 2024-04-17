<?php

namespace Artwork\Modules\Change\Builders;

use Antonrom\ModelChangesHistory\Models\Change;
use App\Models\User;
use Artwork\Modules\Change\Interfaces\Builder;
use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class ChangeBuilder implements Builder
{
    private string $type;

    private string $modelClass;

    private int $modelId;

    private string $translationKey;

    private array $translationKeyPlaceholderValues = [];

    private Shift $shift;

    public static function newInstance(): self
    {
        return new self();
    }

    public function setModelClass(string|object $modelClass): self
    {
        if (is_object($modelClass)) {
            $this->modelClass = $modelClass::class;

            return $this;
        }

        if (!class_exists($modelClass)) {
            throw new InvalidArgumentException(
                'Class "' . $modelClass . '" does not exist. Please provide proper class to set (required).'
            );
        }

        $this->modelClass = $modelClass;

        return $this;
    }

    public function setModelId(int $modelId): self
    {
        $this->modelId = $modelId;

        return $this;
    }

    public function setTranslationKey(string $translationKey): self
    {
        $this->translationKey = $translationKey;

        return $this;
    }

    public function setTranslationKeyPlaceholderValues(array $translationKeyPlaceholderValues): self
    {
        $this->translationKeyPlaceholderValues = $translationKeyPlaceholderValues;

        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setShift(Shift $shift): self
    {
        $this->shift = $shift;

        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function build(): Change
    {
        if (empty($this->modelClass) || !class_exists($this->modelClass)) {
            throw new InvalidArgumentException(
                'Use setModelClass() to provide a proper model class (required).'
            );
        }

        if (empty($this->modelId)) {
            throw new InvalidArgumentException(
                'Use setModelId() to provide a proper model id (required).'
            );
        }

        if (empty($this->translationKey)) {
            throw new InvalidArgumentException(
                'Use setTranslationKey() to provide a proper translation key (required).'
            );
        }

        /** @var User $user */
        $user = Auth::user();
        $changes[] = [
            'type' => $this->type ?? 'project',
            'translationKey' => $this->translationKey,
            'translationKeyPlaceholderValues' => $this->translationKeyPlaceholderValues,
            'changed_by' => [
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
            ]
        ];

        if (!empty($this->type) && $this->type === 'shift') {
            if (empty($this->shift)) {
                throw new InvalidArgumentException(
                    'Use setShift() to provide a proper shift model (required).'
                );
            }

            $changes[] = [
                'event_title' => $this->shift->event->eventName,
                'event_id' => $this->shift->event->id,
                'shift_id' => $this->shift->id,
                'shift_description' => $this->shift->description,
            ];
        }

        return new Change(
            [
                'model_id' => $this->modelId,
                'model_type' => $this->modelClass,
                'changes' => json_encode($changes),
                'change_type' => 'updated',
                'changer_type' => User::class,
                'changer_id' => Auth::id(),
                'created_at' => now()
            ]
        );
    }
}
