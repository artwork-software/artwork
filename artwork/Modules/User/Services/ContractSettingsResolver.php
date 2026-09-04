<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserContractAssign;

/**
 * Liest Vertragswerte "Zuweisung vor Vorlage":
 * Ist das Feld auf der Zuweisung (user_contract_assigns) gesetzt (nicht null), gilt die Zuweisung,
 * sonst der Wert der Vertragsvorlage (user_contracts), sonst der Default.
 *
 * Gilt für Zielwerte (DP-18 "Ist / X"), Dreimonatsflag, Sondertag-Regel und Überstundenregel.
 */
class ContractSettingsResolver
{
    /** @var array<int, UserContractAssign|null> */
    private array $assignCache = [];

    public function assignFor(User $user): ?UserContractAssign
    {
        if (array_key_exists($user->id, $this->assignCache)) {
            return $this->assignCache[$user->id];
        }

        $assign = $user->relationLoaded('contract')
            ? $user->contract
            : UserContractAssign::query()->with('userContract')->where('user_id', $user->id)->first();

        return $this->assignCache[$user->id] = $assign;
    }

    public function templateFor(User $user): ?UserContract
    {
        $assign = $this->assignFor($user);
        if ($assign === null) {
            return null;
        }

        if (!$assign->relationLoaded('userContract')) {
            $assign->load('userContract');
        }

        return $assign->userContract;
    }

    public function value(User $user, string $key, mixed $default = null): mixed
    {
        $assign = $this->assignFor($user);
        if ($assign === null) {
            return $default;
        }

        if (self::hasValue($assign, $key)) {
            return $assign->getAttribute($key);
        }

        $template = $this->templateFor($user);
        if ($template !== null && self::hasValue($template, $key)) {
            return $template->getAttribute($key);
        }

        return $default;
    }

    public function bool(User $user, string $key, bool $default = false): bool
    {
        return (bool) $this->value($user, $key, $default);
    }

    public function int(User $user, string $key, int $default = 0): int
    {
        return (int) $this->value($user, $key, $default);
    }

    public function float(User $user, string $key, float $default = 0.0): float
    {
        return (float) $this->value($user, $key, $default);
    }

    public function flush(): void
    {
        $this->assignCache = [];
    }

    private static function hasValue(UserContractAssign|UserContract $model, string $key): bool
    {
        return array_key_exists($key, $model->getAttributes()) && $model->getAttribute($key) !== null;
    }
}
