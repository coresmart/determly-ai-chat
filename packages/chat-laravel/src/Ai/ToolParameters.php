<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel\Ai;

use Determly\Chat\Tool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Prism\Prism\Contracts\Schema;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\BooleanSchema;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\RawSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Tool as PrismTool;

/**
 * Maps a Determly tool's JSON schema onto Laravel AI and Prism parameter builders.
 * Written against laravel/ai 1.4.0 and prism-php/prism 0.103.0.
 */
final class ToolParameters
{
    /**
     * @return array<string, Type>
     */
    public static function laravel(Tool $tool, JsonSchema $schema): array
    {
        $mapped = [];
        foreach (self::properties($tool) as $name => $property) {
            $mapped[$name] = self::laravelType($schema, $property, self::requiredNames($tool));
            if (in_array($name, self::requiredNames($tool), true)) {
                $mapped[$name] = $mapped[$name]->required();
            }
        }

        return $mapped;
    }

    public static function prism(Tool $tool, bool $execute = false): PrismTool
    {
        $callback = $execute
            ? static function (mixed ...$arguments) use ($tool): string {
                $parameters = [];
                foreach ($arguments as $name => $value) {
                    if (is_string($name)) {
                        $parameters[$name] = $value;
                    }
                }

                return ToolEquivalents::encode($tool->handle($parameters));
            }
            : static fn (mixed ...$arguments): string => '';

        $prism = (new PrismTool)
            ->as($tool->name())
            ->for($tool->description())
            ->using($callback);

        $required = self::requiredNames($tool);
        foreach (self::properties($tool) as $name => $property) {
            $prism = $prism->withParameter(
                self::prismSchema($name, $property),
                in_array($name, $required, true),
            );
        }

        return $prism;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function properties(Tool $tool): array
    {
        $parameters = $tool->parameters();
        $properties = $parameters['properties'] ?? [];
        if (! is_array($properties)) {
            return [];
        }
        $named = [];
        foreach ($properties as $name => $property) {
            if (is_string($name) && is_array($property)) {
                $named[$name] = $property;
            }
        }

        return $named;
    }

    /**
     * @return list<string>
     */
    private static function requiredNames(Tool $tool): array
    {
        $required = $tool->parameters()['required'] ?? [];
        if (! is_array($required)) {
            return [];
        }
        $names = [];
        foreach ($required as $name) {
            if (is_string($name)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * @param  array<string, mixed>  $property
     * @param  list<string>  $required
     */
    private static function laravelType(JsonSchema $schema, array $property, array $required): Type
    {
        $kind = is_string($property['type'] ?? null) ? $property['type'] : 'string';
        $type = match ($kind) {
            'integer' => $schema->integer(),
            'number' => $schema->number(),
            'boolean' => $schema->boolean(),
            'array' => self::laravelArray($schema, $property),
            'object' => $schema->object(self::laravelObject($schema, $property, $required)),
            default => $schema->string(),
        };
        $description = $property['description'] ?? null;
        if (is_string($description) && $description !== '') {
            $type = $type->description($description);
        }
        $enum = $property['enum'] ?? null;
        if (is_array($enum) && $enum !== [] && array_is_list($enum)) {
            $type = $type->enum(array_values($enum));
        }

        return $type;
    }

    /**
     * @param  array<string, mixed>  $property
     */
    private static function laravelArray(JsonSchema $schema, array $property): Type
    {
        $items = $property['items'] ?? [];
        $itemType = is_array($items) ? (is_string($items['type'] ?? null) ? $items['type'] : 'string') : 'string';
        $item = match ($itemType) {
            'integer' => $schema->integer(),
            'number' => $schema->number(),
            'boolean' => $schema->boolean(),
            default => $schema->string(),
        };

        return $schema->array()->items($item);
    }

    /**
     * @param  array<string, mixed>  $property
     * @param  list<string>  $required
     * @return array<string, Type>
     */
    private static function laravelObject(JsonSchema $schema, array $property, array $required): array
    {
        $nested = $property['properties'] ?? [];
        if (! is_array($nested)) {
            return [];
        }
        $mapped = [];
        $nestedRequired = $property['required'] ?? [];
        $nestedRequired = is_array($nestedRequired) ? $nestedRequired : [];
        foreach ($nested as $name => $child) {
            if (! is_string($name) || ! is_array($child)) {
                continue;
            }
            $mapped[$name] = self::laravelType($schema, $child, $required);
            if (in_array($name, $nestedRequired, true)) {
                $mapped[$name] = $mapped[$name]->required();
            }
        }

        return $mapped;
    }

    /**
     * @param  array<string, mixed>  $property
     */
    private static function prismSchema(string $name, array $property): Schema
    {
        $description = is_string($property['description'] ?? null) && $property['description'] !== ''
            ? $property['description']
            : $name;
        $kind = is_string($property['type'] ?? null) ? $property['type'] : 'string';
        $enum = self::enumValues($property);
        if ($enum !== null && in_array($kind, ['string', 'integer', 'number'], true)) {
            return new EnumSchema($name, $description, $enum);
        }

        return match ($kind) {
            'integer' => new RawSchema($name, [
                'description' => $description,
                'type' => 'integer',
            ]),
            'number' => new NumberSchema($name, $description),
            'boolean' => new BooleanSchema($name, $description),
            'array' => new ArraySchema($name, $description, self::prismItem($property)),
            'object' => self::prismObject($name, $description, $property),
            default => new StringSchema($name, $description),
        };
    }

    /**
     * @param  array<string, mixed>  $property
     * @return list<string|int|float>|null
     */
    private static function enumValues(array $property): ?array
    {
        $enum = $property['enum'] ?? null;
        if (! is_array($enum) || $enum === [] || ! array_is_list($enum)) {
            return null;
        }
        $values = [];
        foreach ($enum as $value) {
            if (is_string($value) || is_int($value) || is_float($value)) {
                $values[] = $value;
            }
        }

        return $values === [] ? null : $values;
    }

    /**
     * @param  array<string, mixed>  $property
     */
    private static function prismItem(array $property): Schema
    {
        $items = $property['items'] ?? null;
        if (! is_array($items)) {
            return new StringSchema('item', 'Item');
        }

        return self::prismSchema('item', $items);
    }

    /**
     * @param  array<string, mixed>  $property
     */
    private static function prismObject(string $name, string $description, array $property): ObjectSchema
    {
        $nested = $property['properties'] ?? [];
        $children = [];
        $required = [];
        $requiredNames = $property['required'] ?? [];
        $requiredNames = is_array($requiredNames) ? $requiredNames : [];
        if (is_array($nested)) {
            foreach ($nested as $childName => $child) {
                if (! is_string($childName) || ! is_array($child)) {
                    continue;
                }
                $children[] = self::prismSchema($childName, $child);
                if (in_array($childName, $requiredNames, true)) {
                    $required[] = $childName;
                }
            }
        }

        return new ObjectSchema($name, $description, $children, $required);
    }
}
