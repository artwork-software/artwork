<?php

namespace Artwork\Core\Api\Scramble;

use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Support\Generator\ClassBasedReference;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Types as OpenApi;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use Spatie\LaravelData\Data;

/**
 * Schemata fuer `spatie/laravel-data`-Objekte im OpenAPI-Export (core-api-context-projects).
 *
 * Ohne diese Erweiterung exportiert scramble in der freien Version fuer jede Data-Klasse ein
 * leeres Objekt — die Klasse wird zwar referenziert, traegt aber keine Felder (die Auswertung von
 * laravel-data ist dort Teil der Pro-Version). Der Contract waere damit an genau den Stellen
 * wertlos, an denen artwork-ai ihn braucht (0.4 §5).
 *
 * Gelesen wird der Konstruktor: Name, Typ und Nullbarkeit je Parameter. Verschachtelte
 * Data-Klassen laufen ueber den Transformer und landen als `$ref`; fuer Listen liefert der
 * Konstruktor-Docblock (`@param Foo[] $bar`) den Elementtyp.
 */
class LaravelDataToSchema extends TypeToSchemaExtension
{
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof ObjectType
            && class_exists($type->name)
            && is_a($type->name, Data::class, true);
    }

    /**
     * @param ObjectType $type
     */
    public function toSchema(Type $type): OpenApi\Type
    {
        $reflection = new ReflectionClass($type->name);
        $constructor = $reflection->getConstructor();

        $object = new OpenApi\ObjectType();

        if ($constructor === null) {
            return $object;
        }

        $itemTypes = $this->arrayItemTypes((string) $constructor->getDocComment(), $reflection);

        foreach ($constructor->getParameters() as $parameter) {
            $object->addProperty(
                $parameter->getName(),
                $this->propertySchema($parameter, $itemTypes[$parameter->getName()] ?? null)
            );

            if (!$parameter->getType()?->allowsNull()) {
                $object->addRequired([$parameter->getName()]);
            }
        }

        return $object;
    }

    public function reference(ObjectType $type): Reference
    {
        return ClassBasedReference::create('schemas', $type->name, $this->components);
    }

    private function propertySchema(ReflectionParameter $parameter, ?string $itemType): OpenApi\Type
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType) {
            return new OpenApi\UnknownType();
        }

        $schema = match ($type->getName()) {
            'int' => new OpenApi\IntegerType(),
            'float' => new OpenApi\NumberType(),
            'bool' => new OpenApi\BooleanType(),
            'string' => new OpenApi\StringType(),
            'array' => $this->arraySchema($itemType),
            default => $this->objectSchema($type->getName()),
        };

        return $type->allowsNull() ? $schema->nullable(true) : $schema;
    }

    private function arraySchema(?string $itemType): OpenApi\Type
    {
        $array = new OpenApi\ArrayType();

        if ($itemType !== null && class_exists($itemType)) {
            return $array->setItems($this->openApiTransformer->transform(new ObjectType($itemType)));
        }

        return $array;
    }

    private function objectSchema(string $class): OpenApi\Type
    {
        if (!class_exists($class)) {
            return new OpenApi\UnknownType();
        }

        return $this->openApiTransformer->transform(new ObjectType($class));
    }

    /**
     * Elementtypen aus `@param Foo[] $bar` bzw. `@param array<int, Foo> $bar` des Konstruktors.
     *
     * @param ReflectionClass<object> $reflection
     * @return array<string, string>
     */
    private function arrayItemTypes(string $docComment, ReflectionClass $reflection): array
    {
        if ($docComment === '') {
            return [];
        }

        preg_match_all(
            '/@param\s+(?:array<[^,>]+,\s*)?([\\\\A-Za-z0-9_]+)(?:\[\]|>)\s+\$([A-Za-z0-9_]+)/',
            $docComment,
            $matches,
            PREG_SET_ORDER
        );

        $types = [];

        foreach ($matches as $match) {
            $types[$match[2]] = $this->resolveClass($match[1], $reflection);
        }

        return $types;
    }

    /**
     * @param ReflectionClass<object> $reflection
     */
    private function resolveClass(string $name, ReflectionClass $reflection): string
    {
        if (str_starts_with($name, '\\') || class_exists($name)) {
            return ltrim($name, '\\');
        }

        $sameNamespace = $reflection->getNamespaceName() . '\\' . $name;

        return class_exists($sameNamespace) ? $sameNamespace : $name;
    }
}
