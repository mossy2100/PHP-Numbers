<?php

declare(strict_types=1);

namespace OceanMoon\Core;

use Closure;
use DomainException;
use UnexpectedValueException;
use UnitEnum;

/**
 * Convenience methods for working with types.
 */
final class Types
{
    #region Constructor

    /**
     * Private constructor to prevent instantiation.
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    #endregion

    #region Inspection methods

    /**
     * Get the basic type of a value.
     *
     * Result will be a string, one of:
     * - null
     * - bool
     * - int
     * - float
     * - string
     * - array
     * - enum
     * - closure
     * - object
     * - resource
     * - unknown
     *
     * @param mixed $value The value to get the type of.
     * @return string The basic type of the value.
     */
    public static function getBasicType(mixed $value): string
    {
        // Use get_debug_type() to get the new, canonical type names.
        $type = get_debug_type($value);

        return match (true) {
            // The basics.
            in_array($type, ['null', 'bool', 'int', 'float', 'string', 'array'], true) => $type,
            // Check for special object types.
            $value instanceof UnitEnum => 'enum',
            $value instanceof Closure => 'closure',
            // Call gettype() and return the first word, which should be "object", "resource", or "unknown".
            default => explode(' ', gettype($value))[0]
        };
    }

    #endregion

    #region Formatting methods

    /**
     * Convert any PHP value into a unique string.
     *
     * The intended use case is a key in a collection like Set or Dictionary.
     *
     * @param mixed $value The value to convert.
     * @return string The unique string key.
     * @throws DomainException If an array could not be converted into a unique string.
     * @throws UnexpectedValueException If the value has an unknown type.
     */
    public static function getUniqueString(mixed $value): string
    {
        switch (self::getBasicType($value)) {
            case 'null':
                return 'n';

            case 'bool':
                return 'b:' . ($value ? 'T' : 'F');

            case 'int':
                /** @var int $value */
                return 'i:' . $value;

            case 'float':
                /** @var float $value */
                return 'f:' . Floats::toHex($value);

            case 'string':
                /** @var string $value */
                return 's:' . strlen($value) . ":$value";

            case 'array':
                /** @var array<array-key, mixed> $value */
                return 'a:' . count($value) . ':' . Stringify::stringifyArray($value);

            case 'enum':
                /** @var UnitEnum $value */
                return 'e:' . $value::class . '::' . $value->name;

            case 'closure':
                /** @var Closure $value */
                return 'c:' . spl_object_id($value);

            case 'object':
                /** @var object $value */
                return 'o:' . spl_object_id($value);

            case 'resource':
                /** @var resource $value */
                return 'r:' . get_resource_id($value);

                // @codeCoverageIgnoreStart
            default:
                return throw new UnexpectedValueException('Value has unknown type.');
                // @codeCoverageIgnoreEnd
        }
    }

    #endregion

    #region Type checking methods

    /**
     * Check if two values have the same type (i.e. same scalar type or same class).
     *
     * Uses get_debug_type() for type comparison. This is preferable to comparing using `instanceof`, which will return
     * true for subclasses, or get_class(), which doesn't work for scalars.
     *
     * @param mixed $obj1 The first value to compare.
     * @param mixed $obj2 The second value to compare.
     * @return bool True if the types are the same, false otherwise.
     */
    public static function same(mixed $obj1, mixed $obj2): bool
    {
        return get_debug_type($obj1) === get_debug_type($obj2);
    }

    #endregion

    #region Trait-related methods

    /**
     * Check if an object or class uses a given trait.
     * Handle both class names and objects, including trait inheritance.
     *
     * @param object|string $objOrClass The object or class to inspect.
     * @param string $trait The trait to check for.
     * @return bool True if the object or class uses the trait, false otherwise.
     */
    public static function usesTrait(object|string $objOrClass, string $trait): bool
    {
        $allTraits = self::getTraits($objOrClass);
        return in_array($trait, $allTraits, true);
    }

    /**
     * Get all traits used by an object, class, interface, or trait, including those inherited from parent classes and
     * other traits.
     *
     * @param object|string $objOrClass The object or class (or interface or trait) to inspect.
     * @return list<string> The list of traits used by the object or class.
     * @throws DomainException If the provided class name is invalid.
     */
    public static function getTraits(object|string $objOrClass): array
    {
        // Get class, interface, or trait name.
        if (is_object($objOrClass)) {
            $class = $objOrClass::class;
        } elseif (class_exists($objOrClass) || interface_exists($objOrClass) || trait_exists($objOrClass)) {
            $class = $objOrClass;
        } else {
            throw new DomainException("Invalid class name: $objOrClass. Must be a class, interface, or trait.");
        }

        return self::getTraitsRecursive($class);
    }

    #endregion

    #region Helper methods

    /**
     * Get all traits used by a class, interface, or trait, including parent classes and trait inheritance.
     *
     * @param string $class The class, interface, or trait to inspect.
     * @return list<string> The list of traits used by the type.
     */
    private static function getTraitsRecursive(string $class): array
    {
        // Collection for traits.
        $traits = [];

        // Get traits from current class and all parent classes.
        do {
            // Get traits used by the current class.
            $classTraits = class_uses($class);

            // Check for class not found. Should be never, but having this check satisfies phpstan.
            if ($classTraits === false) {
                break; // @codeCoverageIgnore
            }

            // Add traits from current class. class_uses() keys its result by trait name, so the values must be
            // unpacked via array_values() first - spreading a string-keyed array would pass named arguments.
            array_push($traits, ...array_values($classTraits));

            // Also get traits used by the traits themselves.
            foreach ($classTraits as $trait) {
                array_push($traits, ...self::getTraitsRecursive($trait));
            }

            // Move to parent class.
            $class = get_parent_class($class);
        } while ($class !== false);

        return array_values(array_unique($traits));
    }

    #endregion
}
