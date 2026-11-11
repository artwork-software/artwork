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
 * Data-Klassen laufen ueber den Transformer und landen als `$ref`.
 *
 * Fuer `array`-Parameter entscheidet der Konstruktor-Docblock ueber die Form (Export-Fix 2,
 * 16.09.2026). PHP kennt nur einen Array-Typ, OpenAPI dagegen zwei:
 *
 * | Docblock | Export |
 * |---|---|
 * | `Foo[]`, `array<int, Foo>`, `list<Foo>` | `array` mit `items` |
 * | `array<string, mixed>` | `object` (freie Schluessel) |
 * | `array<string, Foo>` | `object` mit `additionalProperties` |
 *
 * Ohne den Docblock bleibt ein nackter `ArrayType` uebrig — und dessen `items` fuellt scramble
 * still mit `string` (`Generator/Types/ArrayType::__construct()`). Genau so standen
 * `pending_action.args`/`preview` und `AiConversationTurnData.context` faelschlich als
 * `array<string>` im Export, obwohl `contracts/action-contract.md` Objekte verlangt.
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

        $docTypes = $this->paramDocTypes((string) $constructor->getDocComment());

        foreach ($constructor->getParameters() as $parameter) {
            $object->addProperty(
                $parameter->getName(),
                $this->propertySchema($parameter, $docTypes[$parameter->getName()] ?? null, $reflection)
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

    /**
     * @param ReflectionClass<object> $reflection
     */
    private function propertySchema(
        ReflectionParameter $parameter,
        ?string $docType,
        ReflectionClass $reflection
    ): OpenApi\Type {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType) {
            return new OpenApi\UnknownType();
        }

        $schema = match ($type->getName()) {
            'int' => new OpenApi\IntegerType(),
            'float' => new OpenApi\NumberType(),
            'bool' => new OpenApi\BooleanType(),
            'string' => new OpenApi\StringType(),
            'array' => $this->docTypeSchema($docType, $reflection) ?? new OpenApi\ArrayType(),
            default => $this->objectSchema($type->getName()),
        };

        return $type->allowsNull() ? $schema->nullable(true) : $schema;
    }

    /**
     * Ein Docblock-Typausdruck als OpenAPI-Schema; `null`, wenn er nichts hergibt (`mixed`).
     *
     * @param ReflectionClass<object> $reflection
     */
    private function docTypeSchema(?string $expression, ReflectionClass $reflection): ?OpenApi\Type
    {
        $expression = trim((string) $expression);

        if ($expression === '') {
            return null;
        }

        if (str_ends_with($expression, '[]')) {
            return $this->listSchema(substr($expression, 0, -2), $reflection);
        }

        if (preg_match('/^(array|list|iterable)\s*<(.+)>$/s', $expression, $match) === 1) {
            return $this->genericSchema($match[1], $this->splitArguments($match[2]), $reflection);
        }

        return $this->scalarSchema($expression) ?? $this->classSchema($expression, $reflection);
    }

    /**
     * Ein skalarer Docblock-Typ; `null`, wenn der Ausdruck keiner ist (dann ist es ein Klassenname
     * oder etwas, das kein Schema hergibt).
     */
    private function scalarSchema(string $expression): ?OpenApi\Type
    {
        return match (strtolower($expression)) {
            'int', 'integer', 'positive-int', 'negative-int' => new OpenApi\IntegerType(),
            'float', 'double' => new OpenApi\NumberType(),
            'bool', 'boolean', 'true', 'false' => new OpenApi\BooleanType(),
            'string', 'non-empty-string', 'class-string' => new OpenApi\StringType(),
            'array', 'list' => new OpenApi\ArrayType(),
            default => null,
        };
    }

    /**
     * `array<…>`, `list<…>`, `iterable<…>`. Bei zwei Argumenten entscheidet der Schluesseltyp:
     * ganzzahlig = Liste, alles andere = Objekt mit freien Schluesseln.
     *
     * @param string[] $arguments
     * @param ReflectionClass<object> $reflection
     */
    private function genericSchema(string $kind, array $arguments, ReflectionClass $reflection): ?OpenApi\Type
    {
        if (count($arguments) === 1) {
            return $this->listSchema($arguments[0], $reflection);
        }

        if (count($arguments) !== 2) {
            return null;
        }

        [$key, $value] = $arguments;

        if ($kind !== 'array' || $this->isIntegerKey($key)) {
            return $this->listSchema($value, $reflection);
        }

        $object = new OpenApi\ObjectType();
        $valueSchema = $this->docTypeSchema($value, $reflection);

        return $valueSchema === null ? $object : $object->additionalProperties($valueSchema);
    }

    /**
     * @param ReflectionClass<object> $reflection
     */
    private function listSchema(string $itemExpression, ReflectionClass $reflection): OpenApi\Type
    {
        $array = new OpenApi\ArrayType();
        $items = $this->docTypeSchema($itemExpression, $reflection);

        return $items === null ? $array : $array->setItems($items);
    }

    private function isIntegerKey(string $key): bool
    {
        return in_array(strtolower(trim($key)), ['int', 'integer', 'positive-int', 'array-key'], true);
    }

    /**
     * @param ReflectionClass<object> $reflection
     */
    private function classSchema(string $name, ReflectionClass $reflection): ?OpenApi\Type
    {
        $class = $this->resolveClass($name, $reflection);

        return class_exists($class) ? $this->objectSchema($class) : null;
    }

    private function objectSchema(string $class): OpenApi\Type
    {
        if (!class_exists($class)) {
            return new OpenApi\UnknownType();
        }

        return $this->openApiTransformer->transform(new ObjectType($class));
    }

    /**
     * Typausdruck je Konstruktor-Parameter aus `@param <typ> $name`.
     *
     * @return array<string, string>
     */
    private function paramDocTypes(string $docComment): array
    {
        if ($docComment === '') {
            return [];
        }

        preg_match_all('/@param\s+([^$]+?)\s+\$([A-Za-z0-9_]+)/', $docComment, $matches, PREG_SET_ORDER);

        $types = [];

        foreach ($matches as $match) {
            $types[$match[2]] = trim($match[1]);
        }

        return $types;
    }

    /**
     * Kommas auf oberster Ebene trennen; `<>` und `{}` werden dabei nicht zerschnitten.
     *
     * @return string[]
     */
    private function splitArguments(string $arguments): array
    {
        $parts = [];
        $current = '';
        $depth = 0;

        foreach (str_split($arguments) as $character) {
            $depth += match ($character) {
                '<', '{', '(' => 1,
                '>', '}', ')' => -1,
                default => 0,
            };

            if ($character === ',' && $depth === 0) {
                $parts[] = trim($current);
                $current = '';

                continue;
            }

            $current .= $character;
        }

        $parts[] = trim($current);

        return array_values(array_filter($parts, static fn (string $part): bool => $part !== ''));
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
