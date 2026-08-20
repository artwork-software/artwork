<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserWorkTimePattern;
use Database\Seeders\Demo\Support\DemoDataPools;
use Illuminate\Database\Seeder;

/**
 * Nutzerverträge, Arbeitszeitmuster und praxisnahe Regeln.
 * Die Regeln werden über shift_rule_contract_assignments den Verträgen
 * zugeordnet — genau die Verknüpfung, die die Regelverletzungs-Features zeigt.
 */
class DemoContractSeeder extends Seeder
{
    public function run(): void
    {
        $contracts = [];
        foreach (DemoDataPools::CONTRACTS as $poolKey => $data) {
            $attributes = $data;
            unset($attributes['weekly_hours'], $attributes['name']);
            $contracts[$poolKey] = UserContract::updateOrCreate(['name' => $data['name']], $attributes);
        }
        $this->command?->info(sprintf('Verträge: %d angelegt/aktualisiert.', count($contracts)));

        foreach (DemoDataPools::WORK_TIME_PATTERNS as $data) {
            $attributes = $data;
            unset($attributes['name']);
            UserWorkTimePattern::updateOrCreate(['name' => $data['name']], $attributes);
        }
        $this->command?->info('Arbeitszeitmuster angelegt/aktualisiert.');

        foreach (DemoDataPools::SHIFT_RULES as $data) {
            $contractKeys = $data['contracts'];
            unset($data['contracts']);
            $rule = ShiftRule::withTrashed()->updateOrCreate(['name' => $data['name']], $data + ['is_active' => true]);
            if ($rule->trashed()) {
                $rule->restore();
            }

            $contractIds = collect($contractKeys)
                ->map(static fn (string $key) => $contracts[$key]->id ?? null)
                ->filter()
                ->all();
            $rule->contracts()->syncWithoutDetaching($contractIds);
        }
        $this->command?->info(sprintf('Regeln: %d angelegt und Verträgen zugeordnet.', count(DemoDataPools::SHIFT_RULES)));
    }
}
