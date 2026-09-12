<?php

declare(strict_types=1);

namespace OceanMoon\Core;

use InvalidArgumentException;
use JsonException;
use LengthException;

/**
 * Container for useful array-related methods.
 */
final class Arrays
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
     * Checks if an array contains recursion.
     *
     * @param array<array-key, mixed> $arr The array to check.
     * @return bool True if the array contains recursion, false otherwise.
     */
    public static function hasRecursion(array $arr): bool
    {
        try {
            json_encode($arr, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            if ($e->getCode() === JSON_ERROR_RECURSION) {
                return true;
            }
        }

        return false;
    }

    #endregion

    #region String methods

    /**
     * Wrap each string value in the array with quotes.
     *
     * Useful for formatting lists in error messages or output.
     * Does not perform escaping - values containing quotes will not be escaped.
     * Array keys are preserved.
     *
     * @param array<string> $arr Array of strings to quote.
     * @param bool $doubleQuotes Use double quotes instead of single quotes.
     * @return array<string> Array with each value wrapped in quotes.
     * @throws InvalidArgumentException If any array value is not a string.
     */
    public static function quoteValues(array $arr, bool $doubleQuotes = false): array
    {
        $quoteChar = $doubleQuotes ? '"' : "'";

        $quoteFn = static function ($value) use ($quoteChar) {
            // Type check.
            if (!is_string($value)) {
                throw new InvalidArgumentException(
                    'Invalid array value type: ' . get_debug_type($value) . '. Must be string.'
                );
            }

            // Wrap the value in quotes.
            return $quoteChar . $value . $quoteChar;
        };

        // Apply the quotes. array_map() preserves the array keys.
        return array_map($quoteFn, $arr);
    }

    /**
     * Convert an array of strings to a serial list, e.g. 'apples, oranges, and bananas'.
     *
     * The default conjunction is 'and'; a common alternative would be 'or'.
     * Of course, you could use words from other languages, such as 'y' or 'o' (Spanish), or 'et' or 'ou' (French).
     *
     * Array keys are ignored.
     *
     * @param array<string> $arr Array of strings.
     * @param string $conjunction The conjunction to use between the last two items (default 'and').
     * @param bool $oxford If an Oxford comma should be used in lists of 3 or more items (default true).
     * @return string Serial list of strings.
     * @throws InvalidArgumentException If any array value is not a string.
     */
    public static function toSerialList(array $arr, string $conjunction = 'and', bool $oxford = true): string
    {
        // Convert to list array.
        $values = [];

        // Ensure all values are strings.
        foreach ($arr as $value) {
            if (!is_string($value)) {
                throw new InvalidArgumentException(
                    'Invalid array value type: ' . get_debug_type($value) . '. Must be string.'
                );
            }
            $values[] = $value;
        }

        $nItems = count($arr);

        return match ($nItems) {
            0 => '',
            1 => $values[0],
            2 => $values[0] . " $conjunction " . $values[1],
            default => implode(', ', array_slice($values, 0, -1)) . ($oxford ? ',' : '') . " $conjunction " .
                $values[$nItems - 1],
        };
    }

    #endregion

    #region Extraction methods

    /**
     * Get the first value in an array.
     *
     * This is for PHP versions prior to 8.5, which provides the array_first() function.
     *
     * @param non-empty-array<array-key, mixed> $arr The array to extract from.
     * @return mixed The first value in the array.
     * @throws LengthException If the array is empty.
     */
    public static function first(array $arr): mixed
    {
        // Check the array is not empty.
        if (count($arr) === 0) {
            throw new LengthException('Cannot get the first element of an empty array.');
        }

        return $arr[array_key_first($arr)];
    }

    /**
     * Get the last value in an array.
     *
     * This is for PHP versions prior to 8.5, which provides the array_last() function.
     *
     * @param non-empty-array<array-key, mixed> $arr The array to extract from.
     * @return mixed The last value in the array.
     * @throws LengthException If the array is empty.
     */
    public static function last(array $arr): mixed
    {
        // Check the array is not empty.
        if (count($arr) === 0) {
            throw new LengthException('Cannot get the last element of an empty array.');
        }

        return $arr[array_key_last($arr)];
    }

    #endregion

    #region Transformation methods

    /**
     * Remove all instances of a value from an array.
     *
     * Keys are preserved.
     *
     * @param array<array-key, mixed> $arr The original array.
     * @param mixed $valueToRemove The value to remove.
     * @return array<array-key, mixed> A new array without the given value, if it was there.
     */
    public static function removeValue(array $arr, mixed $valueToRemove): array
    {
        return array_filter($arr, static fn ($value) => $value !== $valueToRemove);
    }

    /**
     * Return a copy of an array with any circular (self-referencing) sub-arrays replaced by the RECURSION marker
     * string, so the result can be safely inspected, iterated, or serialized without triggering infinite recursion or a
     * fatal error.
     *
     * PHP arrays can genuinely contain themselves, e.g.:
     * ```php
     * $a = ['x' => 1];
     * $b = &$a;
     * $a[] = $b; // $a now contains a real reference cycle back to itself.
     * ```
     * self::hasRecursion() can already tell you that some recursion exists, using json_encode() and catching its
     * JSON_ERROR_RECURSION error — but that only answers a yes/no question for the whole array; it doesn't say where
     * the recursive reference is, so it isn't enough on its own to know which value to replace. Array `===` compares by
     * value, not by reference identity, so there's no built-in way to ask "is this the same array instance as an
     * ancestor?" either. print_r() performs the same underlying cycle detection internally (implemented in C, with
     * access to the engine's reference-counted array structures), but — usefully here — its text output preserves the
     * position of each recursive reference within the printed structure. This method detects recursion by parsing that
     * positional output, rather than reimplementing cycle detection from scratch.
     *
     * Only genuine reference cycles are detected and replaced; two unrelated sub-arrays that happen to have identical
     * contents are left untouched, since print_r() itself does not flag them as recursive.
     *
     * @param array<array-key, mixed> $arr The array to clean.
     * @return array<array-key, mixed> A copy of $arr with any circular sub-arrays replaced by the
     * RECURSION marker.
     */
    public static function removeRecursion(array $arr): array
    {
        return self::removeRecursionHelper($arr, trim(print_r($arr, true)));
    }

    #endregion

    #region Helper methods

    /**
     * Recursive helper for removeRecursion().
     *
     * @param array<array-key, mixed> $arr The (sub-)array to clean.
     * @param string $printR The trimmed print_r() output for $arr.
     * @return array<array-key, mixed> The cleaned array.
     */
    private static function removeRecursionHelper(array $arr, string $printR): array
    {
        // Handle an empty array.
        if (preg_match('/^Array\s*\(\s*\)$/', $printR)) {
            return [];
        }

        // Strip the outer "Array (" ... ")" wrapper, leaving just the body.
        $body = preg_replace('/^Array\s*\(/', '', $printR) ?? '';
        $body = preg_replace('/\)$/', '', $body) ?? '';

        // Parse the body into a [key => print_r'd value] map, one entry per top-level element.
        $items = self::parsePrintRBody($body);

        // Build a fresh result array rather than starting from a copy of $arr: if an element of
        // $arr is itself a PHP reference (e.g. $arr['x'] = &$other), a plain `$result = $arr;`
        // copy preserves that reference binding, so later assigning to $result[$key] would
        // silently mutate whatever variable it's bound to elsewhere in the caller's scope.
        // Assigning fresh values via foreach avoids that entirely.
        $result = [];
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $parsedValue = trim($items[$key]);
                $isRecursive = preg_match('/^Array\s+' . preg_quote(RECURSION, '/') . '$/', $parsedValue) === 1;
                $result[$key] = $isRecursive ? RECURSION : self::removeRecursionHelper($value, $parsedValue);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Parse the body of a print_r() array dump (with the outer "Array (...)" wrapper already
     * stripped) into a map of top-level keys to their raw print_r'd values.
     *
     * A "new item" is recognized by a "[key] => value" line at the same indent level as the
     * first key found; anything less-indented or without that shape is treated as a continuation
     * of the current item's value (e.g. a nested array's own body).
     *
     * @param string $body The print_r() body, with the outer "Array (...)" wrapper stripped.
     * @return array<array-key, string> Map of key => raw print_r'd value.
     */
    private static function parsePrintRBody(string $body): array
    {
        $lines = explode("\n", $body);

        $items = [];
        $currentKey = null;
        $currentValue = '';
        $indent = null;

        foreach ($lines as $line) {
            $newItem = false;

            if (preg_match('/^(\s*)\[(.*)\] => (.*)/', $line, $matches)) {
                // Establish the indent level from the first key found.
                if ($currentKey === null) {
                    $indent = strlen($matches[1]);
                }

                // Only treat this as a new top-level item if it's at the same indent as the first key.
                if (strlen($matches[1]) === $indent) {
                    $newItem = true;

                    // Store the previous item.
                    if ($currentKey !== null) {
                        $items[$currentKey] = $currentValue;
                    }

                    // Start a new item.
                    $currentKey = $matches[2];
                    $currentValue = $matches[3];
                }
            }

            if (!$newItem) {
                // Append the line to the current value.
                $currentValue .= "\n" . $line;
            }
        }

        // Store the last item.
        if ($currentKey !== null) {
            $items[$currentKey] = $currentValue;
        }

        return $items;
    }

    #endregion
}
