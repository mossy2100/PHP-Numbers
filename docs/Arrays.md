# Arrays

Static utility class containing useful array-related methods.

---

## Overview

The `Arrays` class provides helper methods for working with PHP arrays. This is a static utility class and cannot be
instantiated.

Methods are organized into:

- **Inspection methods** - Analyze array properties (e.g., detect circular references).
- **String methods** - Convert arrays to or format arrays as strings (e.g., quote values, serial lists).
- **Extraction methods** - Extract values from arrays (e.g., first/last element).
- **Transformation methods** - Transform arrays (e.g., remove values).

---

## Inspection Methods

### hasRecursion()

```php
public static function hasRecursion(array $arr): bool
```

Checks if an array contains circular references (recursion). This occurs when an array contains a reference to itself,
either directly or indirectly through nested arrays.

**Parameters:**

- `$arr` (array) - The array to check for circular references.

**Returns:**

- `bool` - Returns `true` if recursion is detected, `false` otherwise.

**Examples:**

Direct recursion:

```php
$arr = ['foo' => 'bar'];
$arr['self'] = &$arr;
Arrays::hasRecursion($arr); // true
```

Indirect recursion:

```php
$arr1 = ['name' => 'array1'];
$arr2 = ['name' => 'array2'];
$arr1['child'] = &$arr2;
$arr2['parent'] = &$arr1;
Arrays::hasRecursion($arr1); // true
```

No recursion:

```php
$arr = [[1, 2], [3, 4]];
Arrays::hasRecursion($arr); // false
```

**Note:** This method uses `json_encode()` internally to detect recursion, as circular references cannot be
JSON-encoded.

---

## String Methods

### quoteValues()

```php
public static function quoteValues(array $arr, bool $doubleQuotes = false): array
```

Wrap each string value in the array with quotes for formatting purposes. Useful for creating quoted lists in error
messages, output, or documentation.

**Parameters:**

- `$arr` (array\<string\>) - Array of strings to quote.
- `$doubleQuotes` (bool) - Use double quotes instead of single quotes (default: `false`).

**Returns:**

- `array<string>` - Array with each value wrapped in quotes, preserving array keys.

**Throws:**

- `InvalidArgumentException` - If any array value is not a string.

**Examples:**

Basic usage with single quotes (default):

```php
$fruits = ['apple', 'banana', 'cherry'];
$quoted = Arrays::quoteValues($fruits);
// ["'apple'", "'banana'", "'cherry'"]
```

Using double quotes:

```php
$names = ['Alice', 'Bob', 'Charlie'];
$quoted = Arrays::quoteValues($names, true);
// ['"Alice"', '"Bob"', '"Charlie"']
```

Preserves array keys:

```php
$config = ['host' => 'localhost', 'port' => '5432'];
$quoted = Arrays::quoteValues($config);
// ['host' => "'localhost'", 'port' => "'5432'"]
```

**Note:** This method does not perform escaping. If the values contain the quote character, they will not be escaped.
For proper escaping, use appropriate functions like `addslashes()`, `Stringify::stringifyString()` or context-specific
escaping functions.

### toSerialList()

```php
public static function toSerialList(array $arr, string $conjunction = 'and', bool $oxford = true): string
```

Convert an array of strings to a serial list (e.g., `'apples, oranges, and bananas'`). By default, the Oxford comma is
used when there are more than two items. Array keys are ignored - the values are treated as a list, so an associative
array works just as well as a plain list.

**Parameters:**

- `$arr` (array) - Array of strings.
- `$conjunction` (string) - The conjunction to use between the last two items (default: `'and'`).
- `$oxford` (bool) - Whether to use an Oxford comma in lists of 3 or more items (default: `true`).

**Returns:**

- `string` - The serial list as a string.

**Throws:**

- `InvalidArgumentException` - If any array value is not a string.

**Examples:**

```php
Arrays::toSerialList([]);                                    // ''
Arrays::toSerialList(['apples']);                             // 'apples'
Arrays::toSerialList(['apples', 'oranges']);                  // 'apples and oranges'
Arrays::toSerialList(['apples', 'oranges', 'bananas']);       // 'apples, oranges, and bananas'
Arrays::toSerialList(['apples', 'oranges', 'bananas'], oxford: false); // 'apples, oranges and bananas'
Arrays::toSerialList(['red', 'green', 'blue'], 'or');         // 'red, green, or blue'
Arrays::toSerialList(['a' => 'apples', 'b' => 'oranges']);    // 'apples and oranges'
```

**Use Case:** Formatting lists in user-facing messages, error messages, or logs.

```php
$validUnits = ['kg', 'g', 'mg'];
throw new DomainException('Invalid unit. Expected ' . Arrays::toSerialList(Arrays::quoteValues($validUnits), 'or') . '.');
// "Invalid unit. Expected 'kg', 'g', or 'mg'."
```

---

## Extraction Methods

### first()

```php
public static function first(array $arr): mixed
```

Get the first value in an array.

This method is only needed for PHP versions prior to 8.5, which provides the native `array_first()` function. However,
this method doesn't behave exactly the same as `array_first()`, as it will throw a `LengthException` instead of
returning `null` for empty arrays. This allows distinguishing an empty array (no first value exists) from an array where
the first value is actually null.

**Parameters:**

- `$arr` (non-empty-array) - The array to extract from.

**Returns:**

- `mixed` - The first value in the array.

**Throws:**

- `LengthException` - If the array is empty.

**Examples:**

```php
Arrays::first([1, 2, 3]);                    // 1
Arrays::first(['a' => 'alpha', 'b' => 'beta']); // 'alpha'
Arrays::first([42]);                          // 42
Arrays::first([]);                            // throws LengthException
```

**Note:** Unlike `reset()`, this method does not modify the array's internal pointer and throws an exception for empty
arrays rather than returning `false`.

### last()

```php
public static function last(array $arr): mixed
```

Get the last value in an array.

This method is only needed for PHP versions prior to 8.5, which provides the native `array_last()` function. This method
doesn't behave exactly the same as `array_last()`, as it will throw a `LengthException` instead of returning `null` for
empty arrays. This allows distinguishing an empty array (no last value exists) from an array where the last value is
actually null.

**Parameters:**

- `$arr` (non-empty-array) - The array to extract from.

**Returns:**

- `mixed` - The last value in the array.

**Throws:**

- `LengthException` - If the array is empty.

**Examples:**

```php
Arrays::last([1, 2, 3]);                    // 3
Arrays::last(['a' => 'alpha', 'b' => 'beta']); // 'beta'
Arrays::last([42]);                          // 42
Arrays::last([]);                            // throws LengthException
```

**Note:** Unlike `end()`, this method does not modify the array's internal pointer and throws an exception for empty
arrays rather than returning `false`.

---

## Transformation Methods

### removeValue()

```php
public static function removeValue(array $arr, mixed $valueToRemove): array
```

Remove all instances of a value from an array. Uses strict comparison (`!==`), so types must match. Keys are preserved.

**Parameters:**

- `$arr` (array) - The original array.
- `$valueToRemove` (mixed) - The value to remove.

**Returns:**

- `array` - A new array without the given value. Keys from the original array are preserved.

**Examples:**

```php
Arrays::removeValue([1, 2, 3, 2, 4], 2);        // [0 => 1, 2 => 3, 4 => 4]
Arrays::removeValue(['a', 'b', 'a', 'c'], 'a');  // [1 => 'b', 3 => 'c']
Arrays::removeValue([1, 2, 3], 99);              // [0 => 1, 1 => 2, 2 => 3]
Arrays::removeValue([], 'anything');             // []
```

Strict comparison means types must match:

```php
Arrays::removeValue([0, '0', false, null], 0);   // [1 => '0', 2 => false, 3 => null]
```

With associative arrays:

```php
Arrays::removeValue(['a' => 1, 'b' => 2, 'c' => 3], 2); // ['a' => 1, 'c' => 3]
```

### removeRecursion()

```php
public static function removeRecursion(array $arr): array
```

Return a copy of an array with any circular sub-arrays replaced by the `RECURSION` marker (see
[`RECURSION`](Globals.md#recursion)), so it can be safely printed, serialized, or otherwise traversed without an
infinite loop. Only genuine reference cycles are replaced; two unrelated sub-arrays that happen to have identical
contents are left untouched.

**Parameters:**

- `$arr` (array) - The array to clean.

**Returns:**

- `array` - A copy of `$arr` with any circular sub-arrays replaced by the `RECURSION` marker.

**Examples:**

```php
$arr = ['x' => 1];
$arr['self'] = &$arr;
Arrays::removeRecursion($arr); // ['x' => 1, 'self' => '*RECURSION*']
```

```php
$arr = [[1, 2], [3, 4]];
Arrays::removeRecursion($arr); // [[1, 2], [3, 4]] (unchanged; no recursion)
```

**Note:** `hasRecursion()` only answers a yes/no question, using `json_encode()` and catching its
`JSON_ERROR_RECURSION` error — it doesn't say *where* the recursive reference is. This method takes a different
approach: it locates each recursive reference by parsing `print_r()`'s recursion-aware output, rather than
reimplementing cycle detection from scratch.

---

## See Also

- **[Stringify](Stringify.md)** - Value-to-string conversion utilities.
- **[Types](Types.md)** - Type checking and conversion utilities.
