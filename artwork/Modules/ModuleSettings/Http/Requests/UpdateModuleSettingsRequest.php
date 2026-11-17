<?php

namespace Artwork\Modules\ModuleSettings\Http\Requests;

use Artwork\Modules\ModuleSettings\Models\ModuleSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use ReflectionClass;
use ReflectionProperty;

class UpdateModuleSettingsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Nur echte Modulschalter: exists:settings,name ließ jeden Einstellungsnamen anderer Gruppen durch
            'menu' => ['required', 'string', Rule::in(self::moduleSwitchNames())],
            'enabled' => 'required|boolean'
        ];
    }

    /**
     * @return list<string>
     */
    private static function moduleSwitchNames(): array
    {
        $properties = (new ReflectionClass(ModuleSettings::class))->getProperties(ReflectionProperty::IS_PUBLIC);

        return array_values(array_map(
            static fn (ReflectionProperty $property): string => $property->getName(),
            array_filter(
                $properties,
                static fn (ReflectionProperty $property): bool => !$property->isStatic()
                    && $property->getDeclaringClass()->getName() === ModuleSettings::class
            )
        ));
    }
}
