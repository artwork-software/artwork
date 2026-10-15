<?php

namespace Artwork\Core\Dto;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use ReflectionClass;
use Spatie\LaravelData\Contracts\BaseDataCollectable;
use Spatie\LaravelData\Contracts\TransformableData;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\DataConfig;
use Spatie\LaravelData\Support\Lazy\InertiaLazy;
use Spatie\LaravelData\Support\Transformation\DataContext;
use Spatie\LaravelData\Support\Transformation\TransformationContext;
use Spatie\LaravelData\Support\Transformation\TransformationContextFactory;

/**
 * Schneller Weg für toArray()/jsonSerialize() von einfachen laravel-data-Objekten, die in großer Zahl
 * serialisiert werden (Kalender-Monat: ~2.000 Termin-/Schicht-DTOs). Die Transformations-Pipeline von
 * laravel-data (Sichtbarkeit, Partials, Transformer je Property) kostete dort ein Vielfaches der Daten
 * selbst; dieselbe Ausgabe entsteht hier direkt aus den Properties.
 *
 * Exakt wie laravel-data mit der Projektkonfiguration: Reihenfolge der Data-Properties, Optional
 * entfällt, Lazy::inertia() wird aufgelöst, Arrays werden unverändert durchgereicht (verschachtelte DTOs
 * darin serialisiert erst json_encode), verschachtelte Data-Objekte werden transformiert. Alles andere —
 * Transformationskontext, Partials (DataContext), Additional-Data/with(), Property-Transformer, Namens-Mapping,
 * versteckte Properties, Datums-/Enum-/Arrayable-Werte, andere Lazy-Arten, Collections — fällt auf die
 * laravel-data-Pipeline zurück.
 */
trait TransformsWithoutDataPipeline
{
    /** @var array<class-string, list<string>|false> */
    private static array $directTransformProperties = [];

    public function transform(
        null|TransformationContextFactory|TransformationContext $transformationContext = null,
    ): array {
        $propertyNames = self::directTransformPropertyNames(static::class);

        if (
            $transformationContext !== null
            || $propertyNames === false
            || $this->_additional !== []
            || !self::hasNoPartials($this->_dataContext)
        ) {
            return parent::transform($transformationContext);
        }

        $payload = [];
        foreach ($propertyNames as $name) {
            $value = $this->{$name};

            if ($value instanceof Optional) {
                continue;
            }

            if ($value instanceof Lazy) {
                if (!$value instanceof InertiaLazy) {
                    return parent::transform();
                }
                $value = $value->resolve();
            } elseif ($value instanceof BaseDataCollectable) {
                return parent::transform();
            } elseif ($value instanceof TransformableData) {
                $value = $value->transform();
            } elseif (
                $value instanceof DateTimeInterface
                || $value instanceof BackedEnum
                || $value instanceof Arrayable
            ) {
                return parent::transform();
            }

            $payload[$name] = $value;
        }

        return $payload;
    }

    /**
     * Ein angelegter, aber leerer DataContext (laravel-data legt ihn bei jeder Pipeline-Transformation an)
     * ändert die Ausgabe nicht — nur gesetzte include/exclude/only/except-Partials tun das.
     */
    private static function hasNoPartials(?DataContext $context): bool
    {
        return $context === null || (
            ($context->includePartials === null || $context->includePartials->count() === 0)
            && ($context->excludePartials === null || $context->excludePartials->count() === 0)
            && ($context->onlyPartials === null || $context->onlyPartials->count() === 0)
            && ($context->exceptPartials === null || $context->exceptPartials->count() === 0)
        );
    }

    /**
     * Property-Namen in laravel-data-Reihenfolge, oder false, wenn die Klasse etwas nutzt, das nur die
     * Pipeline korrekt abbildet.
     *
     * @return list<string>|false
     */
    private static function directTransformPropertyNames(string $class): array|false
    {
        if (array_key_exists($class, self::$directTransformProperties)) {
            return self::$directTransformProperties[$class];
        }

        $reflection = new ReflectionClass($class);
        $partialMethods = [
            'includeProperties',
            'excludeProperties',
            'onlyProperties',
            'exceptProperties',
            'defaultWrap',
            'with',
        ];
        foreach ($partialMethods as $method) {
            if (
                $reflection->hasMethod($method)
                && $reflection->getMethod($method)->getDeclaringClass()->getName() === $class
            ) {
                return self::$directTransformProperties[$class] = false;
            }
        }

        $names = [];
        foreach (app(DataConfig::class)->getDataClass($class)->properties as $property) {
            if ($property->hidden || $property->transformer !== null || $property->outputMappedName !== null) {
                return self::$directTransformProperties[$class] = false;
            }
            $names[] = $property->name;
        }

        return self::$directTransformProperties[$class] = $names;
    }
}
