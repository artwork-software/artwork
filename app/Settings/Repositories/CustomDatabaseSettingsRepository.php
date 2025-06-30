<?php

namespace App\Settings\Repositories;

use Spatie\LaravelSettings\SettingsRepositories\DatabaseSettingsRepository;

class CustomDatabaseSettingsRepository extends DatabaseSettingsRepository
{
    public function updatePropertiesPayload(string $group, array $properties): void
    {
        $propertiesInBatch = collect($properties)->map(function ($payload, $name) use ($group) {
            return [
                'group' => $group,
                'name' => $name,
                'payload' => $this->encode($payload),
                'locked' => false, // Include the locked field with a default value of false
            ];
        })->values()->toArray();

        $this->getBuilder()
            ->where('group', $group)
            ->upsert($propertiesInBatch, ['group', 'name'], ['payload', 'locked']);
    }
}
