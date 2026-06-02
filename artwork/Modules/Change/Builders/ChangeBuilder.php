<?php

namespace Artwork\Modules\Change\Builders;

use Artwork\Modules\Change\Interfaces\Builder;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Models\Activity as DefaultActivity;

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
    public function build(): Activity
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

        $properties = [
            [
                'type' => $this->type ?? 'project',
                'translationKey' => $this->translationKey,
                'translationKeyPlaceholderValues' => $this->translationKeyPlaceholderValues,
            ],
        ];

        if (!empty($this->type) && $this->type === 'shift') {
            if (empty($this->shift)) {
                throw new InvalidArgumentException(
                    'Use setShift() to provide a proper shift model (required).'
                );
            }

            $properties[] = [
                'event_title' => $this->shift?->event?->eventName ?? __('notifications.shift.without_event'),
                'event_id' => $this->shift?->event?->id ?? null,
                'shift_id' => $this->shift->id,
                'shift_description' => $this->shift->description,
            ];
        }

        $causerId = Auth::id();

        $activityClass = config('activitylog.activity_model', DefaultActivity::class);

        /** @var Activity $activity */
        $activity = new $activityClass([
            'log_name' => $this->type ?? 'default',
            'description' => $this->translationKey,
            'subject_type' => $this->modelClass,
            'subject_id' => $this->modelId,
            'event' => 'updated',
            'causer_type' => $causerId !== null ? User::class : null,
            'causer_id' => $causerId,
            'properties' => $properties,
        ]);

        return $activity;
    }
}
