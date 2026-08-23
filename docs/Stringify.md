# Stringify

Static utility class for converting PHP values to readable string representations.

---

## Overview

The `Stringify` class provides an alternative to PHP's built-in functions for converting values to strings (viz.
`echo()`, `print()`, `var_dump()`, `var_export()`, `print_r()`, `json_encode()`, and `serialize()`). This is a static
utility class and cannot be instantiated.

### Key Features

- **Single-quoted strings**: Strings are wrapped in single quotes, with backslash and single quotes escaped. Unicode
  characters are preserved as-is.
- **Clearer float representation**: Floats are always made distinguishable from integers by appending `.0` if no decimal
  point or `E` is present in the string (e.g., `5.0` instead of `5`). Special values (`NAN`, `INF`, `-INF`) are handled
  correctly.
- **PHP-style array formatting**: Both list arrays and associative arrays use square brackets (`[...]`). List arrays omit keys;
  associative arrays show keys with thick arrows (`=>`).
- **Smart pretty printing**: Simple list arrays use single-line, grid, or one-per-line layout depending on length. Associative arrays and objects align keys/property names.
- **UML-style visibility notation**: Objects use `ClassName #id {...}` with UML visibility symbols (`+` public, `#` protected,
  `-` private).
- **Enum support**: Enums are rendered as `Fully\Qualified\ClassName::CaseName`.
- **Closure support**: Closures are rendered as their original source code, obtained from the file they were
  declared in.
- **Resource formatting**: Resources show both the id (via `get_resource_id()`) and the resource type from
  `get_debug_type()`, e.g. `resource #5 (stream)`.

The output for scalars, strings, enums, arrays, and closures is parseable PHP code. Object and resource output is not parseable,
but is designed for readability.

---

## Constants

| Constant                  | Value | Description                                                                                     |
| ------------------------- | ----- | ----------------------------------------------------------------------------------------------- |
| `DEFAULT_INDENT`          | `4`   | Default number of spaces per indentation level in pretty-printed output.                        |
| `DEFAULT_MAX_LINE_LENGTH` | `120` | Default maximum line length before pretty-printed list arrays wrap to grid or multiline format. |

---

## Configuration Methods

The indent and max line length are configurable via static properties. Changes persist for the lifetime of the process
and affect all subsequent calls.

### setIndent() / getIndent()

```php
public static function setIndent(int $indent): void
public static function getIndent(): int
```

Set or get the number of spaces used for each indentation level in pretty-printed output.

**Throws:**

- `DomainException` - If the supplied indent is negative.

**Example:**

```php
Stringify::setIndent(2);
Stringify::stringify(['a' => 1, 'b' => 2], true);
// [
//   'a' => 1,
//   'b' => 2,
// ]
```

### setMaxLineLength() / getMaxLineLength()

```php
public static function setMaxLineLength(int $maxLineLength): void
public static function getMaxLineLength(): int
```

Set or get the maximum line length for pretty-printed output. This only affects when list arrays wrap from a compact, single-line format to a grid or one-value-per-line format. Other long values (strings, etc.) aren't chopped down to this length.

**Throws:**

- `DomainException` - If the max line length is not greater than 0.

**Example:**

```php
Stringify::setMaxLineLength(60);
Stringify::stringify(range(1, 20), true);
// Grid layout will wrap earlier due to shorter max line length.
```

### resetDefaults()

```php
public static function resetDefaults(): void
```

Reset both indent and max line length to their default constant values. Useful in test teardown to avoid leaking state
between tests.

**Example:**

```php
Stringify::setIndent(2);
Stringify::setMaxLineLength(80);
// ... do work ...
Stringify::resetDefaults(); // Back to 4 spaces and 120 chars.
```

---

## Main Stringification Methods

### stringify()

```php
public static function stringify(mixed $value, bool $prettyPrint = false, int $indentLevel = 0): string
```

Convert any PHP value to a readable string representation. This is the main entry point that dispatches to the
appropriate type-specific method.

**Parameters:**

- `$value` (mixed) - The value to encode.
- `$prettyPrint` (bool) - Whether to use pretty printing with indentation (default: `false`).
- `$indentLevel` (int) - The level of indentation for nested structures (default: `0`).

**Returns:**

- `string` - The string representation of the value.

**Throws:**

- `DomainException` - If the value cannot be stringified (e.g., a string whose encoding can't be detected or
  converted to UTF-8).
- `UnexpectedValueException` - If the value has an unknown type (probably never happen).

**Examples:**

Basic types:

```php
Stringify::stringify(null);          // 'null'
Stringify::stringify(true);          // 'true'
Stringify::stringify(42);            // '42'
Stringify::stringify('hello');       // "'hello'"
Stringify::stringify(3.14);          // '3.14'
Stringify::stringify(5.0);           // '5.0' (not '5')
```

Arrays:

```php
Stringify::stringify([1, 2, 3]);                       // '[1, 2, 3]'
Stringify::stringify(['name' => 'John', 'age' => 30]); // "['name' => 'John', 'age' => 30]"
```

Enums:

```php
Stringify::stringify(Suit::Hearts); // 'App\Enums\Suit::Hearts'
```

Closures:

```php
Stringify::stringify(static fn (float $x): float => $x * $x); // 'static fn (float $x): float => $x * $x'
```

### toString()

```php
public static function toString(mixed $value): string
```

Convert any value to a string, without errors or warnings.

**Behavior:**

1. Tries PHP's default `(string)` cast first, with warnings temporarily promoted to exceptions so that cases that usually emit a warning are caught.
2. Otherwise, falls back to `Stringify::stringify()` — this handles `NAN`, arrays, non-`Stringable` objects, resources, and anything else the cast couldn't.

**Parameters:**

- `$value` (mixed) - The value to convert.

**Returns:**

- `string` - The value as a string.

**Examples:**

```php
echo Stringify::toString('hello');     // 'hello'
echo Stringify::toString(42);          // '42'
echo Stringify::toString(true);        // '1'
echo Stringify::toString(null);        // ''
echo Stringify::toString([1, 2, 3]);   // '[1, 2, 3]' (via Stringify)
echo Stringify::toString(NAN);         // 'NAN' (via Stringify; a direct cast would emit a warning)

class CodeMonkey {
    public $name = 'Shaun';
}
echo Stringify::toString(new CodeMonkey);  // 'CodeMonkey #9 {+name => 'Shaun'}' (via Stringify)
```

### abbrev()

```php
public static function abbrev(mixed $value, int $maxLen = 32): string
```

Get a short string representation of a value, truncated to a maximum length. Uses multibyte-safe truncation. Useful for
error messages and logs where space is limited.

**Parameters:**

- `$value` (mixed) - The value to get the string representation for.
- `$maxLen` (int) - The maximum length of the result (default: `32`, minimum: `3`).

**Returns:**

- `string` - The abbreviated string representation.

**Throws:**

- `DomainException` - If the maximum length is less than 3, or if the value cannot be stringified.
- `UnexpectedValueException` - If the value's type cannot be inferred.

**Examples:**

```php
Stringify::abbrev('hello');                            // "'hello'"
Stringify::abbrev('this is a very long string', 15);   // "'this is a ve…'"
Stringify::abbrev([1, 2, 3, 4, 5, 6, 7], 15);          // '[1, 2, 3, 4, …]'
```

### prepEx()

```php
public static function prepEx(string $message, mixed ...$values): string
```

Prepare an exception message, substituting each `?` placeholder in `$message`, left to right, with `Stringify::abbrev()`
of the matching value. Intended for building exception messages that report one or more offending values without risking
an overly long message for large arrays, strings, or objects.

A value that itself contains `?` is inserted verbatim — it is not re-scanned for further placeholders, since all
splitting happens against the original `$message` before any substitution occurs.

**Parameters:**

- `$message` (string) - The message template, with a `?` placeholder for each value.
- `...$values` (mixed) - The values to substitute, one per placeholder, in order.

**Returns:**

- `string` - The prepared message.

**Throws:**

- `BadMethodCallException` - If the number of values doesn't match the number of placeholders.

**Examples:**

```php
Stringify::prepEx('Invalid minimum: ?. Must be finite.', $min);
// "Invalid minimum: -INF. Must be finite."

Stringify::prepEx('Invalid range: [?, ?]. Min must not exceed max.', $min, $max);
// "Invalid range: [-5, -10]. Min must not exceed max."
```

Typical usage, building an exception message directly in a `throw`:

```php
throw new DomainException(Stringify::prepEx('Invalid minimum: ?. Must be finite.', $min));
```

---

## Type-Specific Stringification Methods

### stringifyBool()

```php
public static function stringifyBool(bool $value): string
```

Stringify a boolean.

**Parameters:**

- `$value` (bool) - The boolean value to encode.

**Returns:**

- `string` - `'true'` or `'false'`.

**Examples:**

```php
Stringify::stringifyBool(true);   // 'true'
Stringify::stringifyBool(false);  // 'false'
```

### stringifyInt()

```php
public static function stringifyInt(int $value): string
```

Stringify an integer.

**Parameters:**

- `$value` (int) - The integer value to encode.

**Returns:**

- `string` - The string representation of the integer.

**Examples:**

```php
Stringify::stringifyInt(42);   // '42'
Stringify::stringifyInt(-7);   // '-7'
```

### stringifyFloat()

```php
public static function stringifyFloat(float $value): string
```

Format a float value as a string, ensuring it doesn't look like an integer. Non-finite values (`NAN`, `INF`, `-INF`) are
returned as-is.

**Parameters:**

- `$value` (float) - The float value to encode.

**Returns:**

- `string` - The string representation of the float.

**Examples:**

```php
Stringify::stringifyFloat(3.14);     // '3.14'
Stringify::stringifyFloat(5.0);      // '5.0' (ensures decimal point)
Stringify::stringifyFloat(1.5e100);  // '1.5E+100'
Stringify::stringifyFloat(-0.0);     // '-0.0'
Stringify::stringifyFloat(NAN);      // 'NAN'
Stringify::stringifyFloat(INF);      // 'INF'
Stringify::stringifyFloat(-INF);     // '-INF'
```

### stringifyString()

```php
public static function stringifyString(string $value): string
```

Convert a string to a parseable single-quoted string representation. Backslashes and single quotes are escaped.
Non-UTF-8 input is converted to UTF-8. Unicode characters are preserved as-is (not escaped to `\uXXXX`).

**Parameters:**

- `$value` (string) - The string value to encode.

**Returns:**

- `string` - The single-quoted, escaped string representation.

**Throws:**

- `DomainException` - If the string is not UTF-8 and the encoding could not be detected or the string could not be converted to UTF-8.

**Examples:**

```php
Stringify::stringifyString('hello');      // "'hello'"
Stringify::stringifyString("it's");       // "'it\\'s'"
Stringify::stringifyString('foo\\bar');   // "'foo\\\\bar'"
Stringify::stringifyString('café');       // "'café'"
```

### stringifyArray()

```php
public static function stringifyArray(
    array $arr,
    bool $prettyPrint = false,
    int $indentLevel = 0,
    bool $alreadyCleaned = false,
): string
```

Stringify a PHP array as concise, parseable code. List arrays (sequential integer keys starting at 0) show values only.
Associative arrays show keys and values with fat arrows (`=>`).

When pretty printing is enabled, three layout strategies are used for list arrays of "simple" items (i.e. nulls and scalars):

1. **Single line** - if the result fits within the configured max line length.
2. **Grid** - items padded to equal width and arranged in columns.
3. **One per line** - for list arrays containing non-scalar values.

List arrays of complex items are always one value per line when pretty printing.

Associative arrays are always one pair per line with aligned keys when pretty printing.

The max line length is controlled by `setMaxLineLength()` (default: `120`).

**Parameters:**

- `$arr` (array) - The array to encode.
- `$prettyPrint` (bool) - Whether to use pretty printing (default: `false`).
- `$indentLevel` (int) - The level of indentation (default: `0`).
- `$alreadyCleaned` (bool) - Whether `$arr` is already known to be free of circular references, e.g. because an
  ancestor call already cleaned it via `Arrays::removeRecursion()`. Internal callers pass this to avoid a
  redundant pass; you shouldn't normally need to set it yourself (default: `false`).

**Returns:**

- `string` - The string representation of the array.

**Throws:**

- `DomainException` - If a value cannot be stringified.

**Examples:**

List arrays:

```php
Stringify::stringifyArray([1, 2, 3]);           // '[1, 2, 3]'
Stringify::stringifyArray([]);                  // '[]'
```

Associative arrays:

```php
Stringify::stringifyArray(['a' => 1, 'b' => 2]); // "['a' => 1, 'b' => 2]"
```

Pretty-printed grid (scalar list exceeding max line length):

```php
Stringify::stringifyArray(range(1, 50), true);
// [
//     1,  2,  3,  4,  5,  6,  7,  8,  9,  10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29,
//     30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50,
// ]
```

Pretty-printed associative array (aligned keys):

```php
Stringify::stringifyArray(['name' => 'John', 'age' => 30], true);
// [
//     'name' => 'John',
//     'age'  => 30,
// ]
```

### stringifyEnum()

```php
public static function stringifyEnum(UnitEnum $value): string
```

Get a string representation of an enum case in the form `Fully\Qualified\ClassName::CaseName`.

**Parameters:**

- `$value` (UnitEnum) - The enum case to stringify.

**Returns:**

- `string` - The string representation.

**Examples:**

```php
Stringify::stringifyEnum(Suit::Hearts);  // 'App\Enums\Suit::Hearts'
```

**Note:** `stringify()` automatically detects enum instances and delegates to this method. `stringifyObject()` does
not — call `stringifyEnum()` directly for an enum case, since passing one to `stringifyObject()` stringifies it as
a plain object instead.

### stringifyClosure()

```php
public static function stringifyClosure(Closure $value): string
```

Get a string representation of a closure by reading back its original source code from the file it was declared in.
The code is returned exactly as written, including its original whitespace and comments; the surrounding
assignment/statement (e.g. a trailing `;`) is not included.

**Parameters:**

- `$value` (Closure) - The closure to stringify.

**Returns:**

- `string` - The closure's original source code, or `''` if unavailable (e.g. the closure has no file, such as one
  created via `Closure::fromCallable()` wrapping an internal function, or the file has changed on disk since the
  closure was declared).

**Examples:**

```php
$sqr = static fn (float $x): float => $x * $x;
Stringify::stringifyClosure($sqr);  // 'static fn (float $x): float => $x * $x'

$cube = function (float $x): float {
    return $x ** 3;
};
Stringify::stringifyClosure($cube);
// "function (float \$x): float {\n    return \$x ** 3;\n}"
```

A closure embedded inline (e.g. passed directly as a call argument) is captured without the surrounding code:

```php
Stringify::stringifyClosure((fn ($x) => $x * 2));  // 'fn ($x) => $x * 2'
```

### stringifyObject()

```php
public static function stringifyObject(object $obj, bool $prettyPrint = false, int $indentLevel = 0): string
```

Stringify an object using a custom format with the class name, curly braces, and UML visibility symbols.

Enum cases are not given special treatment here — pass one and it's stringified as a plain object rather than via
`stringifyEnum()`'s `ClassName::CaseName` form. `stringify()` avoids this by dispatching enums to `stringifyEnum()`
directly, before `stringifyObject()` is ever reached.

**Parameters:**

- `$obj` (object) - The object to encode.
- `$prettyPrint` (bool) - Whether to use pretty printing (default: `false`).
- `$indentLevel` (int) - The level of indentation (default: `0`).

**Returns:**

- `string` - The string representation of the object.

**Throws:**

- `DomainException` - If a value cannot be stringified.

**Visibility Symbols (UML notation):**

- `+` - Public property
- `#` - Protected property
- `-` - Private property

**Examples:**

Simple object:

```php
class User {
    public string $name = 'John';
    protected int $age = 30;
    private string $id = 'abc123';
}

$user = new User();
Stringify::stringifyObject($user);
// "User #1 {+name => 'John', #age => 30, -id => 'abc123'}"
```

Empty object:

```php
$obj = new stdClass();
Stringify::stringifyObject($obj);  // 'stdClass #2 {}'
```

With pretty printing, property names are aligned:

```php
Stringify::stringifyObject($user, true);
// User #1 {
//     +name => 'John',
//     +age  => 30,
//     -id   => 'abc123',
// }
```

Anonymous class:

```php
$anon = new class { public int $x = 1; };
Stringify::stringifyObject($anon);  // 'class@anonymous #3 {+x => 1}'
```

### stringifyResource()

```php
public static function stringifyResource(mixed $value): string
```

Stringify a resource. Combines the resource id (via `get_resource_id()`) with the resource type from
`get_debug_type()`. Works for both open and closed resources -- `is_resource()` returns `false` for a closed resource,
so the type is checked via `get_debug_type()` instead.

**Parameters:**

- `$value` (mixed) - The resource to stringify.

**Returns:**

- `string` - The string representation of the resource, e.g. `'resource #5 (stream)'`.

**Throws:**

- `InvalidArgumentException` - If the value is not a resource.

**Examples:**

```php
$file = fopen('php://memory', 'r');
Stringify::stringifyResource($file);  // 'resource #5 (stream)'

fclose($file);
Stringify::stringifyResource($file);  // 'resource #5 (closed)'
```

---

## See Also

- **[Types](Types.md)** - Type checking and inspection utilities.
- **[Arrays](Arrays.md)** - Array utility methods including `hasRecursion()`.
