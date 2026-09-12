<?php

declare(strict_types=1);

namespace OceanMoon\Core;

use BadMethodCallException;
use Closure;
use DomainException;
use ErrorException;
use InvalidArgumentException;
use ReflectionFunction;
use Throwable;
use UnexpectedValueException;
use UnitEnum;

/**
 * This class provides a method of formatting any PHP value as a string, with a few differences from the default
 * options of echo(), print(), var_dump(), var_export(), print_r(), json_encode(), and serialize().
 *
 * - Floats never look like integers.
 * - Strings are single-quoted.
 * - Arrays are rendered as parseable PHP code using modern square bracket syntax.
 * - Arrays that are lists will not show keys; associative arrays will show keys.
 * - Objects are rendered like arrays but with a class name and curly braces instead of square brackets.
 * - Object properties are shown with UML visibility modifiers: + (public), # (protected), and - (private).
 * - Enums are rendered as Fully\Qualified\ClassName::CaseName
 * - Resources show both id and type.
 *
 * The stringify results for objects and resources are not parseable by PHP, but they are for other types.
 *
 * The purpose of the class is to offer a somewhat more concise, readable, and informative alternative to the usual
 * options. This can be useful for exception, log, and debug messages.
 */
final class Stringify
{
    #region Constants

    /**
     * The default number of spaces to indent each level.
     */
    public const int DEFAULT_INDENT = 4;

    /**
     * The default maximum line length for pretty-printed output.
     */
    public const int DEFAULT_MAX_LINE_LENGTH = 120;

    /**
     * The current number of spaces to indent each level.
     */
    private static int $indent = self::DEFAULT_INDENT;

    /**
     * The current maximum line length for pretty-printed output.
     */
    private static int $maxLineLength = self::DEFAULT_MAX_LINE_LENGTH;

    /**
     * Object IDs (via spl_object_id()) currently being stringified by stringifyObject(), forming a
     * stack that mirrors the call chain of nested calls. Used to detect genuine object-to-object
     * reference cycles.
     *
     * Arrays::removeRecursion() can't see this kind of cycle: it only inspects array values, and an
     * object property is never an array. Array recursion detection has to lean on print_r()'s own
     * cycle detection because plain PHP arrays have no reliable identity check from userland code —
     * but objects do, via spl_object_id(), so a simple visited-set here is enough; there's no need
     * to involve print_r() for this case.
     *
     * @var array<int, true>
     */
    private static array $objectsBeingStringified = [];

    #endregion

    #region Configuration

    /**
     * Set the number of spaces used for each indentation level.
     *
     * @param int $indent The number of spaces (must be >= 0).
     * @throws DomainException If the indent is negative.
     */
    public static function setIndent(int $indent): void
    {
        if ($indent < 0) {
            throw new DomainException("Invalid indent: $indent. Must not be negative.");
        }
        self::$indent = $indent;
    }

    /**
     * Get the current number of spaces used for each indentation level.
     *
     * @return int The current indent value.
     */
    public static function getIndent(): int
    {
        return self::$indent;
    }

    /**
     * Set the maximum line length for pretty-printed output.
     *
     * @param int $maxLineLength The maximum line length (must be > 0).
     * @throws DomainException If the max line length is not positive.
     */
    public static function setMaxLineLength(int $maxLineLength): void
    {
        if ($maxLineLength <= 0) {
            throw new DomainException("Invalid max line length: $maxLineLength. Must be positive.");
        }
        self::$maxLineLength = $maxLineLength;
    }

    /**
     * Get the current maximum line length for pretty-printed output.
     *
     * @return int The current max line length value.
     */
    public static function getMaxLineLength(): int
    {
        return self::$maxLineLength;
    }

    /**
     * Reset indent and max line length to their default values.
     */
    public static function resetDefaults(): void
    {
        self::$indent = self::DEFAULT_INDENT;
        self::$maxLineLength = self::DEFAULT_MAX_LINE_LENGTH;
    }

    #endregion

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

    #region Main stringification methods

    /**
     * Convert a value to a readable string representation.
     *
     * @param mixed $value The value to encode.
     * @param bool $prettyPrint Whether to use pretty printing with indentation (default false).
     * @param int $indentLevel The level of indentation for this structure (default 0).
     * @return string The string representation of the value.
     * @throws DomainException If the value cannot be stringified.
     * @throws UnexpectedValueException If the value has an unknown type. Defensive only: every type
     * Types::getBasicType() can currently return is handled above, so this isn't reachable in practice.
     */
    public static function stringify(mixed $value, bool $prettyPrint = false, int $indentLevel = 0): string
    {
        // Call the relevant method.
        switch (Types::getBasicType($value)) {
            case 'null':
                assert($value === null);
                return 'null';

            case 'bool':
                assert(is_bool($value));
                return self::stringifyBool($value);

            case 'int':
                assert(is_int($value));
                return self::stringifyInt($value);

            case 'float':
                assert(is_float($value));
                return self::stringifyFloat($value);

            case 'string':
                assert(is_string($value));
                return self::stringifyString($value);

            case 'array':
                assert(is_array($value));
                return self::stringifyArray($value, $prettyPrint, $indentLevel);

            case 'enum':
                assert($value instanceof UnitEnum);
                return self::stringifyEnum($value);

            case 'closure':
                assert($value instanceof Closure);
                return self::stringifyClosure($value);

            case 'object':
                assert(is_object($value));
                return self::stringifyObject($value, $prettyPrint, $indentLevel);

            case 'resource':
                return self::stringifyResource($value);

            default:
                // @codeCoverageIgnoreStart
                throw new UnexpectedValueException('Value has unknown type.');
                // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Convert any value to a string, without errors.
     *
     * - Default string conversion is used when it works.
     * - Falls back to stringify() for values that emit warnings or errors on default string conversion (e.g. arrays,
     *   NAN, non-Stringable objects).
     *
     * @param mixed $value Whatever you want converted to a string.
     * @return string The value as a string.
     */
    public static function toString(mixed $value): string
    {
        // Temporarily convert warnings to exceptions to catch cases where the default cast would emit a warning.
        set_error_handler(static fn () => throw new ErrorException());

        try {
            return (string) $value; // @phpstan-ignore cast.string
        } catch (Throwable) {
            // Fall through to the DateTimeInterface/Stringify handling below.
        } finally {
            restore_error_handler();
        }

        // Fallback to stringify() which will handle anything else.
        return self::stringify($value);
    }

    /**
     * Get a short string representation of the given value for use in error messages, log messages, and the like.
     *
     * @param mixed $value The value to get the string representation for.
     * @param int $maxLen The maximum length of the result. The minimum value is 3.
     * @return string The short string representation.
     * @throws DomainException If the maximum length is less than the minimum, or if the value cannot be stringified.
     * @throws UnexpectedValueException If the type cannot be inferred. Defensive only — see stringify(), which this
     * method calls internally; not reachable in practice.
     */
    public static function abbrev(mixed $value, int $maxLen = 32): string
    {
        // Check the max length.
        if ($maxLen < 3) {
            throw new DomainException("Invalid maximum string length: $maxLen. Must be at least 3.");
        }

        // Get the value as a string without newlines or indentation.
        $result = self::stringify($value);

        // Get the basic type.
        $type = Types::getBasicType($value);

        // Abbreviate if necessary, but only for certain types.
        if (in_array($type, ['string', 'array', 'object'], true) && mb_strlen($result) > $maxLen) {
            $truncateAt = $maxLen - 2;

            // For objects, never truncate before the end of the class name. +1 to include the brace itself
            // (mb_strpos() gives the index of the brace, i.e. the count of characters before it).
            if ($type === 'object') {
                $bracePos = mb_strpos($result, '{');
                if ($bracePos !== false) {
                    $truncateAt = max($truncateAt, $bracePos + 1);
                }
            }

            $result = mb_substr($result, 0, $truncateAt) . '…' . match ($type) {
                'string' => "'",
                'array' => ']',
                'object' => '}'
            };

            if ($type === 'object') {
                // If an object is rendered as "ClassName #99 {…}" and this is still too long, trim off the braces.
                if (mb_strlen($result) > $maxLen) {
                    $result = mb_substr($result, 0, mb_strlen($result) - 4);
                }

                // If it's still too long, trim off the id.
                if (mb_strlen($result) > $maxLen) {
                    $hashPos = mb_strrpos($result, '#');
                    $result = mb_substr($result, 0, $hashPos - 1);
                }
            }
        }

        return $result;
    }

    /**
     * Prepare an exception message, substituting each '?' placeholder, left to right, with abbrev() of the
     * matching value.
     *
     * A value that itself contains '?' is inserted verbatim — it is not re-scanned for further placeholders, since
     * all splitting happens against the original $message before any substitution occurs.
     *
     * For example:
     * ```php
     * Stringify::prepEx('Invalid range: [?, ?]. Min must not exceed max.', $min, $max);
     * // "Invalid range: [-5, -10]. Min must not exceed max."
     * ```
     *
     * @param string $message The message template, with a '?' placeholder for each value.
     * @param mixed ...$values The values to substitute, one per placeholder, in order.
     * @return string The prepared message.
     * @throws BadMethodCallException If the number of values doesn't match the number of placeholders.
     */
    public static function prepEx(string $message, mixed ...$values): string
    {
        $parts = explode('?', $message);
        $placeholderCount = count($parts) - 1;

        if ($placeholderCount !== count($values)) {
            throw new BadMethodCallException(
                self::prepEx(
                    'Cannot prepare exception message due to incorrect value count: ?. Expected ?.',
                    count($values),
                    $placeholderCount
                )
            );
        }

        $result = $parts[0];
        foreach (array_values($values) as $i => $value) {
            $result .= self::abbrev($value) . $parts[$i + 1];
        }

        return $result;
    }

    #endregion

    #region Type-specific stringification methods

    /**
     * Stringify a boolean.
     *
     * @param bool $value The boolean value to encode.
     * @return string 'true' or 'false'.
     */
    public static function stringifyBool(bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    /**
     * Stringify an integer.
     *
     * @param int $value The integer value to encode.
     * @return string The string representation of the integer.
     */
    public static function stringifyInt(int $value): string
    {
        return (string) $value;
    }

    /**
     * Encode a float in such a way that it doesn't look like an integer.
     *
     * @param float $value The float value to encode.
     * @return string The string representation of the float.
     */
    public static function stringifyFloat(float $value): string
    {
        // Handle non-finite values. Avoids warning thrown when casting NAN to string.
        if (!is_finite($value)) {
            return var_export($value, true);
        }

        // Cast float to a string.
        $s = (string) $value;

        // If the string representation of the float value has no decimal point or exponent (i.e. nothing to distinguish
        // it from an integer), append a decimal point.
        if (preg_match('/[.eE]/', $s) === 0) {
            $s .= '.0';
        }

        return $s;
    }

    /**
     * Convert a string to a parseable single-quoted string.
     *
     * @param string $value The string value to encode.
     * @return string The single-quoted, escaped string representation.
     * @throws DomainException If the string is not UTF-8 and the encoding could not be detected or the string could not
     * be converted to UTF-8.
     */
    public static function stringifyString(string $value): string
    {
        if ($value === RECURSION) {
            return RECURSION;
        }

        // Get the string as UTF-8 if not already.
        if (!mb_check_encoding($value, 'UTF-8')) {
            // Try to detect the encoding.
            $encoding = mb_detect_encoding($value, mb_detect_order(), true);
            if ($encoding === false) {
                throw new DomainException('String encoding cannot be detected.');
            }

            // Convert the string to UTF-8.
            $value = mb_convert_encoding($value, 'UTF-8', $encoding);
            if ($value === false) {
                // @codeCoverageIgnoreStart
                throw new DomainException('String cannot be converted to UTF-8.');
                // @codeCoverageIgnoreEnd
            }
        }

        // Replace special characters with escape codes. The backslash must be escaped first: str_replace() with
        // parallel arrays processes each pattern in order over the whole string, so if it ran later, it would
        // also catch (and double-escape) the backslashes just inserted by other replacements.
        $search = ['\\', "'"];
        $replace = ['\\\\', "\\'"];
        return "'" . str_replace($search, $replace, $value) . "'";
    }

    /**
     * Stringify a PHP array as concise, parseable code.
     *
     * A list (i.e. an array with sequential integer keys starting at 0) will show values only.
     * An associative array will show keys and values. String keys will be quoted.
     *
     * If pretty printing is enabled, the result will be formatted with new lines and indentation.
     *
     * Any circular (self-referencing) sub-arrays are replaced with the RECURSION marker via
     * Arrays::removeRecursion() before stringifying, so a recursive array can still be stringified
     * (as much as possible) rather than failing outright.
     *
     * @param array<array-key, mixed> $arr The array to encode.
     * @param bool $prettyPrint Whether to use pretty printing (default false).
     * @param int $indentLevel The level of indentation for this structure (default 0).
     * @param bool $alreadyCleaned Whether $arr is already known to be free of circular references —
     * e.g. because it's a nested array within a value that an ancestor call already cleaned via
     * Arrays::removeRecursion(), which cleans the entire subtree in one pass. Sub-arrays discovered
     * while stringifying $arr's own values are always passed down as already cleaned; this only
     * needs to be set explicitly by internal callers, since Arrays::removeRecursion() is otherwise
     * safe (but redundant) to run again on data that's already clean.
     * @return string The string representation of the array.
     * @throws DomainException If a value cannot be stringified.
     */
    public static function stringifyArray(
        array $arr,
        bool $prettyPrint = false,
        int $indentLevel = 0,
        bool $alreadyCleaned = false
    ): string {
        // Replace any circular references with the RECURSION marker so the rest of the array can still be
        // stringified. Skipped if the caller already knows $arr is clean, to avoid redundantly re-scanning a
        // subtree that an ancestor's Arrays::removeRecursion() call already cleaned in full.
        if (!$alreadyCleaned) {
            $arr = Arrays::removeRecursion($arr);
        }

        return array_is_list($arr)
            ? self::stringifyListArray($arr, $prettyPrint, $indentLevel)
            : self::stringifyAssociativeArray($arr, $prettyPrint, $indentLevel);
    }

    /**
     * Get a string representation of an enum case in the form "Fully\Qualified\ClassName::CaseName".
     *
     * @param UnitEnum $value The enum case to stringify.
     * @return string The string representation (e.g. "UnitSystem::Financial").
     */
    public static function stringifyEnum(UnitEnum $value): string
    {
        return $value::class . '::' . $value->name;
    }

    /**
     * Get a string representation of a closure by reading back its original source code, e.g.
     * "static fn (float $x): float => $x * $x" for `$sqr = static fn (float $x): float => $x * $x;`.
     *
     * Uses ReflectionFunction to locate the file and starting line the closure was declared on, then tokenizes the
     * source from that point via token_get_all() to precisely find where the closure's own code actually begins and
     * ends -- unlike a plain text scan, PHP's own tokenizer already resolves whether a `;`/`{`/`}` character is real
     * syntax or just part of a string literal or comment (both become single opaque tokens), so those never get
     * mistaken for closure boundaries. See findClosureTokenRange() for how the boundaries themselves are found.
     * The code is returned exactly as written (including its original whitespace and comments); the surrounding
     * assignment/statement (e.g. a trailing `;`) is not included.
     *
     * Returns '' if the source isn't available (e.g. no file, such as for a closure created via
     * `Closure::fromCallable()` on a non-Closure callable), isn't readable, or if the closure's boundaries couldn't
     * be determined from the tokens (e.g. the file has changed on disk since the closure was declared).
     *
     * @param Closure $value The closure to stringify.
     * @return string The closure's original source code, or '' if unavailable.
     */
    public static function stringifyClosure(Closure $value): string
    {
        $reflection = new ReflectionFunction($value);
        $filename = $reflection->getFileName();

        if ($filename === false || !is_readable($filename)) {
            return '';
        }

        $fileLines = file($filename);
        if ($fileLines === false) {
            return ''; // @codeCoverageIgnore
        }

        // Slice from the closure's start line to EOF. getStartLine() is line-only (no column), and the closure's
        // true end (found below) could be later than getEndLine() if it's embedded in a longer expression, so
        // there's no tighter upper bound to slice to.
        $source = '<?php' . PHP_EOL . implode('', array_slice($fileLines, $reflection->getStartLine() - 1));
        $tokens = token_get_all($source);

        $range = self::findClosureTokenRange($tokens);
        if ($range === null) {
            return ''; // @codeCoverageIgnore
        }
        [$startIndex, $endIndex] = $range;

        $code = '';
        for ($i = $startIndex; $i <= $endIndex; $i++) {
            $code .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
        }

        return rtrim($code);
    }

    /**
     * Stringify an object.
     *
     * The resulting string uses a custom format.
     * - the fully qualified class name is used (i.e. with the namespace) before the opening brace
     * - property names are not quoted
     * - property-value pairs use fat arrows (=>) and are comma-separated
     * - the visibility of each property is shown using UML notation (+ for public, # for protected, - for private)
     *
     * If pretty printing is enabled, the result will be formatted with new lines and indentation.
     *
     * Detects genuine object-to-object reference cycles (an object that directly or indirectly
     * contains itself) via a stack of spl_object_id()s currently being rendered, and replaces a
     * repeated object with the RECURSION marker instead of recursing forever.
     *
     * @param object $obj The object to encode.
     * @param bool $prettyPrint Whether to use pretty printing (default false).
     * @param int $indentLevel The level of indentation for this structure (default 0).
     * @return string The string representation of the object.
     * @throws DomainException If a value cannot be stringified.
     */
    public static function stringifyObject(object $obj, bool $prettyPrint = false, int $indentLevel = 0): string
    {
        // Detect a reference cycle: this exact object is already being stringified further up the call chain.
        $objectId = spl_object_id($obj);
        if (isset(self::$objectsBeingStringified[$objectId])) {
            return RECURSION;
        }
        self::$objectsBeingStringified[$objectId] = true;

        try {
            // Get the object's class and id.
            $classWithId = get_debug_type($obj) . ' #' . $objectId;

            // Convert the object to an array to get its properties. Remove any recursion.
            $arr = Arrays::removeRecursion((array) $obj);

            // Early return if no properties.
            if (count($arr) === 0) {
                return $classWithId . ' {}';
            }

            // Generate the strings for key-value pairs. Each will be on its own line if pretty printing is enabled.
            [$nSpacesBracketIndent, $bracketIndent, $nSpacesItemIndent, $itemIndent] =
                self::prepareIndents($indentLevel, $prettyPrint);

            $keys = array_keys($arr);
            $values = array_values($arr);
            $propNames = [];
            $viz = [];
            $maxPropNameLen = 0;

            foreach ($values as $i => $value) {
                // Split the array key on null bytes to get the property name.
                $key = $keys[$i];
                $nameParts = explode("\0", $key);
                $propNames[$i] = Arrays::last($nameParts);

                // Get the property visibility symbol.
                if (count($nameParts) === 1) {
                    // Property is public.
                    $viz[$i] = '+';
                } else {
                    // Property must be protected or private. If the second item in the $nameParts array is '*', the
                    // property is protected; otherwise, it's private.
                    $viz[$i] = $nameParts[1] === '*' ? '#' : '-';
                }

                // Track the maximum property name length.
                assert(is_string($propNames[$i]));
                $propNameLen = mb_strlen($propNames[$i]);
                if ($propNameLen > $maxPropNameLen) {
                    $maxPropNameLen = $propNameLen;
                }
            }

            // Generate the property => value pairs.
            $pairs = [];
            foreach ($propNames as $i => $propName) {
                assert(is_string($propName));
                $paddedPropName = $prettyPrint ? mb_str_pad($propName, $maxPropNameLen) : $propName;
                $valueStr = self::stringifyCleanedValue($values[$i], $prettyPrint, $indentLevel + 1);
                $pairs[] = $viz[$i] . $paddedPropName . ' => ' . $valueStr;
            }

            // If pretty print, return string formatted with new lines and indentation. Include trailing comma
            // after every pair (including the last), matching stringifyListArray()/stringifyAssociativeArray().
            if ($prettyPrint) {
                $result = "$classWithId {\n";
                foreach ($pairs as $pair) {
                    $result .= $itemIndent . $pair . ",\n";
                }
                return $result . $bracketIndent . '}';
            }

            // Return string without newlines or extra spaces.
            return "$classWithId {" . implode(', ', $pairs) . '}';
        } finally {
            unset(self::$objectsBeingStringified[$objectId]);
        }
    }

    /**
     * Stringify a resource.
     *
     * Result combines the result of casting the resource to string with the resource type.
     * Example: 'Resource id #15 (stream)'
     *
     * @param mixed $value The resource to stringify.
     * @return string The string representation of the resource.
     * @throws InvalidArgumentException If the value is not a resource.
     */
    public static function stringifyResource(mixed $value): string
    {
        // We can't type hint for resource, and is_resource() returns false for a closed resource.
        // So, check the debug type.
        $type = get_debug_type($value);
        if (!str_starts_with($type, 'resource (')) {
            throw new InvalidArgumentException("Invalid value type: $type. Must be a resource.");
        }

        /** @var resource $value */
        return 'resource #' . get_resource_id($value) . substr($type, 8);
    }

    #endregion

    #region Helper methods

    /**
     * Stringify a value that is an element of an array whose own recursion has already been removed
     * via Arrays::removeRecursion(). Array values are guaranteed clean by that ancestor call, so
     * they're routed to stringifyArray() with $alreadyCleaned = true to skip re-scanning an
     * already-clean subtree; everything else goes through the normal stringify() dispatch.
     *
     * @param mixed $value The value to stringify.
     * @param bool $prettyPrint Whether to use pretty printing.
     * @param int $indentLevel The level of indentation for this structure.
     * @return string The string representation of the value.
     * @throws DomainException If the value cannot be stringified.
     */
    private static function stringifyCleanedValue(mixed $value, bool $prettyPrint, int $indentLevel): string
    {
        return is_array($value)
            ? self::stringifyArray($value, $prettyPrint, $indentLevel, true)
            : self::stringify($value, $prettyPrint, $indentLevel);
    }

    /**
     * Stringify a list (sequential integer keys starting at 0).
     *
     * Without pretty printing, values are comma-separated on one line (compact format)
     * With pretty printing, uses compact, grid, or one-per-line format, depending on content.
     *
     * @param list<mixed> $arr The list to stringify.
     * @param bool $prettyPrint Whether to use pretty printing.
     * @param int $indentLevel The level of indentation for this structure.
     * @return string The string representation of the list.
     * @throws DomainException If a value cannot be stringified.
     */
    private static function stringifyListArray(array $arr, bool $prettyPrint, int $indentLevel): string
    {
        // Get the values as strings.
        $values = array_values($arr);
        $valueStrings = array_map(static fn ($value) => self::stringifyCleanedValue($value, $prettyPrint, 0), $values);

        // Generate the compact (single-line) format. No trailing comma.
        $compactList = '[' . implode(', ', $valueStrings) . ']';

        // If we don't want pretty printing, return the compact format.
        if (!$prettyPrint) {
            return $compactList;
        }

        // Set up for multi-line pretty printing.
        [$nSpacesBracketIndent, $bracketIndent, $nSpacesItemIndent, $itemIndent] =
            self::prepareIndents($indentLevel, $prettyPrint);

        $nItems = count($arr);

        // Check if all values are simple (nulls or scalars).
        $allSimple = array_all($arr, static fn ($value) => $value === null || is_scalar($value));

        // If all values are simple, use compact or grid format.
        if ($allSimple) {
            // If the compact format fits on one line, return it.
            if (mb_strlen($compactList) <= self::$maxLineLength - $nSpacesBracketIndent) {
                return $compactList;
            }

            // Get the max item width.
            $maxValueWidth = 0;
            foreach ($valueStrings as $valueString) {
                $len = mb_strlen($valueString);
                if ($len > $maxValueWidth) {
                    $maxValueWidth = $len;
                }
            }

            // Calculate the number of items per line.
            $nItemsPerLine = (int) floor((self::$maxLineLength + 1 - $nSpacesItemIndent) / ($maxValueWidth + 2));
            if ($nItemsPerLine <= 0) {
                $nItemsPerLine = 1;
            }

            // Generate the grid.
            $gridList = "[\n";
            $itemCountThisLine = 0;
            foreach ($valueStrings as $i => $valueString) {
                // Indent the first item on the line.
                if ($itemCountThisLine === 0) {
                    $gridList .= $itemIndent;
                }

                $itemCountThisLine++;
                $isLastOnRow = $itemCountThisLine === $nItemsPerLine || $i === $nItems - 1;

                if ($isLastOnRow) {
                    // Last item on row: no padding to avoid trailing whitespace.
                    $gridList .= $valueString . ",\n";
                    $itemCountThisLine = 0;
                } else {
                    // Non-last item: pad to uniform width, then space.
                    $gridList .= mb_str_pad($valueString . ',', $maxValueWidth + 1) . ' ';
                }
            }
            return $gridList . $bracketIndent . ']';
        }

        // Multiline format.
        $multilineList = "[\n";
        foreach ($values as $value) {
            // Pretty print each value. Include trailing comma.
            $multilineList .= $itemIndent . self::stringifyCleanedValue($value, true, $indentLevel + 1) . ",\n";
        }
        return $multilineList . $bracketIndent . ']';
    }

    /**
     * Stringify a dictionary (non-sequential or string keys).
     *
     * Without pretty printing, key-value pairs are comma-separated on one line.
     * With pretty printing, uses one pair per line with aligned keys.
     *
     * @param array<array-key, mixed> $arr The dictionary to stringify.
     * @param bool $prettyPrint Whether to use pretty printing.
     * @param int $indentLevel The level of indentation for this structure.
     * @return string The string representation of the associative array.
     * @throws DomainException If a value cannot be stringified.
     */
    private static function stringifyAssociativeArray(array $arr, bool $prettyPrint, int $indentLevel): string
    {
        // Get keys as strings.
        $keyStrings = [];
        foreach (array_keys($arr) as $key) {
            $keyStrings[] = self::stringify($key);
        }

        $values = array_values($arr);
        $nItems = count($arr);

        // Unpretty format. No newlines or extra spaces.
        if (!$prettyPrint) {
            $pairs = [];
            for ($i = 0; $i < $nItems; $i++) {
                $pairs[] = $keyStrings[$i] . ' => ' . self::stringifyCleanedValue($values[$i], false, 0);
            }
            return '[' . implode(', ', $pairs) . ']';
        }

        // Set up for pretty printing.
        [$nSpacesBracketIndent, $bracketIndent, $nSpacesItemIndent, $itemIndent] =
            self::prepareIndents($indentLevel, $prettyPrint);

        // Get the maximum key width.
        $maxKeyWidth = 0;
        foreach ($keyStrings as $keyString) {
            $keyStrLen = mb_strlen($keyString);
            if ($keyStrLen > $maxKeyWidth) {
                $maxKeyWidth = $keyStrLen;
            }
        }

        // Generate the result string.
        $result = "[\n";
        for ($i = 0; $i < $nItems; $i++) {
            $result .= $itemIndent . mb_str_pad($keyStrings[$i], $maxKeyWidth) . ' => ' .
                self::stringifyCleanedValue($values[$i], true, $indentLevel + 1) . ",\n";
        }
        return $result . $bracketIndent . ']';
    }

    /**
     * Find the token index range (inclusive) of a closure's own source, within a token stream that starts at or
     * before the closure's declaration (see stringifyClosure()). Locates the first `function`/`fn` keyword
     * (optionally preceded by `static`) as the start, then determines the end based on which of the two closure
     * syntaxes it is:
     * - `function (...) {...}` ends at the matching closing brace for the body, plus a trailing `;` if one
     *   immediately follows (the closure was a complete statement, e.g. `$f = function () {...};`).
     * - `fn (...) => expr` has no closing delimiter of its own -- its body is a bare expression -- so it ends at
     *   the first `;`, `,`, `)`, `]`, or `}` reached while at the same bracket depth the arrow itself was found at
     *   (tracked via findArrowBodyEnd()). A `;` means the closure was a complete statement and is included; the
     *   others mean it's embedded inline as an argument or array value (e.g. `array_map(fn ($x) => $x * 2, $arr)`)
     *   and are excluded, since they belong to the surrounding code, not the closure.
     *
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens Tokens from token_get_all().
     * @return array{0: int, 1: int}|null [$startIndex, $endIndex], or null if no closure keyword was found, or its
     * end couldn't be determined (e.g. unbalanced/truncated source).
     */
    private static function findClosureTokenRange(array $tokens): ?array
    {
        $count = count($tokens);

        // Find the `function`/`fn` keyword.
        $keywordIndex = null;
        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            if (is_array($token) && ($token[0] === T_FUNCTION || $token[0] === T_FN)) {
                $keywordIndex = $i;
                break;
            }
        }
        if ($keywordIndex === null) {
            // Should never happen - a real closure's source always contains one of these.
            return null; // @codeCoverageIgnore
        }
        $isArrow = $tokens[$keywordIndex][0] === T_FN;

        // Include an immediately preceding `static`, if present.
        $startIndex = $keywordIndex;
        for ($j = $keywordIndex - 1; $j >= 0; $j--) {
            if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                continue;
            }
            if (is_array($tokens[$j]) && $tokens[$j][0] === T_STATIC) {
                $startIndex = $j;
            }
            break;
        }

        // Scan forward past the parameter list (and return type, if any) to find the `{` or `=>` that starts the
        // body. Only `(`/`)` need tracking here (not `[`/`{`), since nothing else can legally appear between the
        // keyword and the parameter list, and depth-tracking the parameter list's own parens (including any nested
        // in default value expressions) is enough to know when it's closed.
        $depth = 0;
        $sawParams = false;
        $bodyStartIndex = null;
        for ($i = $keywordIndex + 1; $i < $count; $i++) {
            $text = is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];

            if ($text === '(') {
                $depth++;
                continue;
            }
            if ($text === ')') {
                $depth--;
                if ($depth === 0) {
                    $sawParams = true;
                }
                continue;
            }
            if ($depth === 0 && $sawParams) {
                if ($text === '{') {
                    $bodyStartIndex = $i;
                    break;
                }
                if (is_array($tokens[$i]) && $tokens[$i][0] === T_DOUBLE_ARROW) {
                    $bodyStartIndex = $i;
                    break;
                }
            }
        }
        if ($bodyStartIndex === null) {
            // Should never happen - unbalanced/truncated source.
            return null; // @codeCoverageIgnore
        }

        $endIndex = $isArrow
            ? self::findArrowBodyEnd($tokens, $bodyStartIndex)
            : self::findMatchingBrace($tokens, $bodyStartIndex);
        if ($endIndex === null) {
            // Should never happen - unbalanced/truncated source.
            return null; // @codeCoverageIgnore
        }

        return [$startIndex, $endIndex];
    }

    /**
     * Find the token index of the closing brace matching the `{` at $openIndex, by depth-counting `{`/`}` from
     * there. Safe against `{`/`}` characters that are actually part of a string literal or comment (rather than
     * real syntax), since token_get_all() already collapses those into single opaque tokens whose text (quotes,
     * slashes, etc. included) never equals the bare single-character strings this compares against.
     *
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens Tokens from token_get_all().
     * @param int $openIndex Index of the opening `{`.
     * @return int|null Index of the matching `}`, or null if unbalanced (truncated source).
     */
    private static function findMatchingBrace(array $tokens, int $openIndex): ?int
    {
        $depth = 0;
        for ($i = $openIndex; $i < count($tokens); $i++) {
            $text = is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
            if ($text === '{') {
                $depth++;
            } elseif ($text === '}') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        // Should never happen - unbalanced/truncated source.
        return null; // @codeCoverageIgnore
    }

    /**
     * Find the token index of the natural end of an arrow function's body, given the index of its `=>`. An arrow
     * body is a bare expression with no closing delimiter of its own, so its end is the first `;`, `,`, `)`, `]`,
     * or `}` reached while at the same bracket depth the `=>` itself was found at -- any bracket opened within the
     * expression (e.g. a nested call `fn ($x) => foo($x, [1, 2])`) is depth-tracked and excluded from consideration
     * until its own matching close is seen.
     *
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens Tokens from token_get_all().
     * @param int $arrowIndex Index of the `=>` token.
     * @return int|null Index of the last token to include, which will be the token immediately before the terminating
     * `,`/`)`/`]`/`}`), or null if the expression never terminates (truncated source).
     */
    private static function findArrowBodyEnd(array $tokens, int $arrowIndex): ?int
    {
        $depth = 0;
        for ($i = $arrowIndex + 1; $i < count($tokens); $i++) {
            $text = is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];

            if ($text === '(' || $text === '[' || $text === '{') {
                $depth++;
                continue;
            }
            if ($text === ')' || $text === ']' || $text === '}') {
                if ($depth === 0) {
                    return $i - 1;
                }
                $depth--;
                continue;
            }
            if ($depth === 0 && ($text === ';' || $text === ',')) {
                return $i - 1;
            }
        }
        return null; // @codeCoverageIgnore
    }

    /**
     * Prepare indent values for pretty printing.
     *
     * The two indent strings are empty when $prettyPrint is false, so callers can use them unconditionally
     * without an extra branch; the two indent widths are still computed either way.
     *
     * @param int $indentLevel The current nesting depth (0 for the outermost structure).
     * @param bool $prettyPrint Whether pretty printing is enabled.
     * @return array{0: int, 1: string, 2: int, 3: string} [$nSpacesBracketIndent, $bracketIndent,
     * $nSpacesItemIndent, $itemIndent]: the number of spaces to indent a closing bracket and that many spaces as
     * a string, followed by the number of spaces to indent an item and that many spaces as a string.
     */
    private static function prepareIndents(int $indentLevel, bool $prettyPrint): array
    {
        $nSpacesBracketIndent = $indentLevel * self::$indent;
        $bracketIndent = $prettyPrint ? str_repeat(' ', $nSpacesBracketIndent) : '';
        $nSpacesItemIndent = $nSpacesBracketIndent + self::$indent;
        $itemIndent = $prettyPrint ? str_repeat(' ', $nSpacesItemIndent) : '';
        return [$nSpacesBracketIndent, $bracketIndent, $nSpacesItemIndent, $itemIndent];
    }

    #endregion
}
