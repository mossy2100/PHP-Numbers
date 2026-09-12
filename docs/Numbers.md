# Numbers

Static utility class for general number-related operations.

---

## Overview

The `Numbers` class provides utilities for working with numbers (both integers and floats), including type checking,
equality comparison, and sign operations. This is a static utility class and cannot be instantiated.

### Key Features

- Type checking that distinguishes actual numbers from numeric strings.
- Finite-number checking that excludes the non-finite float values `INF`, `-INF`, and `NAN`.
- Equality comparison that correctly handles mixed int/float types.
- Sign operations with support for IEEE-754 signed zeros (-0.0 vs +0.0).

---

## Inspection Methods

### isNumber()

```php
public static function isNumber(mixed $value): bool
```

Check if a value is a number (int or float). This differs from PHP's built-in `is_numeric()`, which also returns `true`
for numeric strings like `"42"` or `"3.14"`.

**Parameters:**

- `$value` (mixed) - The value to check.

**Returns:** `bool` - `true` if the value is an `int` or `float`, `false` otherwise.

**Examples:**

```php
Numbers::isNumber(42);        // true
Numbers::isNumber(3.14);      // true
Numbers::isNumber(INF);       // true
Numbers::isNumber(NAN);       // true
Numbers::isNumber('42');      // false (numeric string)
Numbers::isNumber('hello');   // false
Numbers::isNumber(true);      // false
Numbers::isNumber(null);      // false
```

**Comparison with `is_numeric()`:**

| Value    | `isNumber()` | `is_numeric()` |
| -------- | ------------ | -------------- |
| `42`     | `true`       | `true`         |
| `3.14`   | `true`       | `true`         |
| `'42'`   | `false`      | `true`         |
| `'3.14'` | `false`      | `true`         |
| `'0x1A'` | `false`      | `true`         |
| `true`   | `false`      | `false`        |
| `null`   | `false`      | `false`        |

### isFiniteNumber()

```php
public static function isFiniteNumber(mixed $value): bool
```

Check if a value is a finite number (an `int` or a finite `float`). This is like `isNumber()`, except that the
non-finite float values `INF`, `-INF`, and `NAN` return `false`. Integers are always finite, so only floats are passed
to PHP's `is_finite()`.

Like `isNumber()`, this differs from PHP's built-in `is_numeric()`, which also returns `true` for numeric strings like
`"42"` or `"3.14"`.

**Parameters:**

- `$value` (mixed) - The value to check.

**Returns:** `bool` - `true` if the value is an `int` or a finite `float`, `false` otherwise.

**Examples:**

```php
Numbers::isFiniteNumber(42);          // true
Numbers::isFiniteNumber(3.14);        // true
Numbers::isFiniteNumber(-0.0);        // true
Numbers::isFiniteNumber(PHP_INT_MAX); // true
Numbers::isFiniteNumber(INF);         // false (positive infinity)
Numbers::isFiniteNumber(-INF);        // false (negative infinity)
Numbers::isFiniteNumber(NAN);         // false (not a number)
Numbers::isFiniteNumber('42');        // false (numeric string)
Numbers::isFiniteNumber('hello');     // false
Numbers::isFiniteNumber(true);        // false
Numbers::isFiniteNumber(null);        // false
```

**Comparison with `isNumber()`:**

| Value  | `isFiniteNumber()` | `isNumber()` |
| ------ | ------------------ | ------------ |
| `42`   | `true`             | `true`       |
| `3.14` | `true`             | `true`       |
| `-0.0` | `true`             | `true`       |
| `INF`  | `false`            | `true`       |
| `-INF` | `false`            | `true`       |
| `NAN`  | `false`            | `true`       |
| `'42'` | `false`            | `false`      |
| `true` | `false`            | `false`      |
| `null` | `false`            | `false`      |

---

## Comparison Methods

### equal()

```php
public static function equal(int|float $a, int|float $b): bool
```

Check if two numbers (integers or floats) are equal. This method is useful for equality comparison when working with
values that can be ints or floats.

This method prioritises comparing the two values as ints. The usual, naive way of comparing two numbers using strict
equality (i.e. `===`) by first converting both to floats can result in false positives because different large integers
may convert to the same float. This happens because 64-bit integers have more precision than the 53 bits available in a
float's mantissa.

The method also avoids the need for loose equality using `==` or `!=`, which several coding standards and IDEs complain about. Furthermore, by requiring `int` or `float` parameters only, it eliminates unexpected bugs caused by PHP's behavior of silently converting numeric strings to numbers and comparing them as such.

**Parameters:**

- `$a` (int|float) - The first number
- `$b` (int|float) - The second number

**Returns:** `bool` - Returns `true` if the numbers are equal, `false` otherwise.

**Behavior:**

- If both values have the same type, compares using strict equality (`===`).
- For mixed int/float comparisons, check if the float can be losslessly converted to an equal integer.

**Examples:**

Integer comparisons:

```php
Numbers::equal(5, 5);    // true
Numbers::equal(5, -5);   // false
```

Zero comparisons:

```php
Numbers::equal(0, 0);     // true
Numbers::equal(0.0, 0);   // true
Numbers::equal(-0.0, 0);  // true
```

Float comparisons (exact):

```php
Numbers::equal(1.0, 1.0);  // true
Numbers::equal(1.0, 2.0);  // false

// Precision issues with floats
Numbers::equal(0.1 + 0.2, 0.3);  // false (!)
```

Mixed type comparisons:

```php
Numbers::equal(5, 5.0);    // true (5.0 converts losslessly to int 5)
Numbers::equal(5, 5.5);    // false (5.5 cannot convert to int)
```

Special float values:

```php
Numbers::equal(INF, INF);    // true
Numbers::equal(INF, -INF);   // false
Numbers::equal(NAN, NAN);    // false (NAN is never equal to itself)
Numbers::equal(0.0, -0.0);   // true
```

Large but exactly representable values work correctly even beyond `Floats::MAX_SAFE_INT` (2^53 - 1) - `equal()` only
needs the float to exactly equal a specific int, not to survive an int→float→int round-trip, so `Floats::isSafeInt()` is
not the right tool for this method's internals:

```php
Numbers::equal(2 ** 60, 2.0 ** 60);  // true (2^60 is a power of two, exactly representable as a float)
```

`PHP_INT_MIN` is a power of two, so it casts to `float` exactly; `PHP_INT_MAX` does not - it rounds up to `2^63`, which
is outside the representable `int` range and therefore never equal to any `int`:

```php
Numbers::equal(PHP_INT_MIN, (float) PHP_INT_MIN);  // true
Numbers::equal(PHP_INT_MAX, (float) PHP_INT_MAX);  // false
```

**Use Cases:**

- Comparing values that may be either int or float
- Exact integer comparisons without IDE warnings about `==` vs `===`
- Checking for specific values like zero or infinity

**Note:** For float comparisons where precision issues may occur, use `Floats::approxEqual()` instead.

---

## Sign Methods

### sign()

```php
public static function sign(int|float $value, bool $zeroForZero = true): int
```

Get the sign of a number. This method supports two modes of operation depending on how you want zero values to be
handled.

**Parameters:**

- `$value` (int|float) - The number to check
- `$zeroForZero` (bool) - If `true` (default), return 0 for any zero value; if `false`, return the sign of the zero (-1 for -0.0,
  1 otherwise)

**Returns:** `int` - Returns 1 for positive, -1 for negative, or 0 for zero when `$zeroForZero` is `true` (default).

**Throws:** `DomainException` - If `$value` is NAN (NAN has no defined sign).

**Examples:**

Default behavior (return 0 for zero):

```php
Numbers::sign(42);      // 1
Numbers::sign(-42);     // -1
Numbers::sign(0);       // 0
Numbers::sign(0.0);     // 0
Numbers::sign(-0.0);    // 0
Numbers::sign(INF);     // 1
Numbers::sign(-INF);    // -1
```

With `$zeroForZero = false` (distinguish between -0.0 and +0.0):

```php
Numbers::sign(42, false);      // 1
Numbers::sign(-42, false);     // -1
Numbers::sign(0, false);       // 1 (integer 0 is considered positive)
Numbers::sign(0.0, false);     // 1
Numbers::sign(-0.0, false);    // -1
```

**Use Cases:**

- Mathematical algorithms requiring signum function.
- Comparisons where sign matters.
- Working with IEEE-754 operations that distinguish -0.0 from +0.0.

### copySign()

```php
public static function copySign(int|float $num, int|float $signSource): int|float
```

Copy the sign of one number to another. Returns a value with the magnitude of the first parameter and the sign of the
second parameter.

If `$num` is `PHP_INT_MIN` and `$signSource` is positive, the result will be a float, because
`abs(PHP_INT_MIN)` can't be expressed as an int. In all other cases, the result has the same type as `$num`.

**Parameters:**

- `$num` (int|float) - The number whose magnitude to use.
- `$signSource` (int|float) - The number whose sign to copy.

**Returns:** `int|float` - The magnitude of `$num` with the sign of `$signSource`.

**Throws:** `DomainException` - If NAN is passed as either parameter (NAN has no defined sign).

**Examples:**

Basic usage:

```php
Numbers::copySign(5, 10);      // 5 (positive magnitude, positive sign)
Numbers::copySign(5, -10);     // -5 (positive magnitude, negative sign)
Numbers::copySign(-5, 10);     // 5 (negative magnitude, positive sign)
Numbers::copySign(-5, -10);    // -5 (negative magnitude, negative sign)
```

With zero:

```php
Numbers::copySign(5, 0.0);     // 5 (sign of +0.0 is positive)
Numbers::copySign(5, -0.0);    // -5 (sign of -0.0 is negative)
Numbers::copySign(0.0, -10);   // -0.0 (zero with negative sign)
```

With infinity:

```php
Numbers::copySign(5, INF);     // 5
Numbers::copySign(5, -INF);    // -5
Numbers::copySign(INF, -10);   // -INF
```

With PHP_INT_MIN:

```php
Numbers::copySign(PHP_INT_MIN, -10);   // PHP_INT_MIN (int, sign already correct)
Numbers::copySign(PHP_INT_MIN, 10);    // 9.223372036854776E+18 (float, magnitude overflows int)
```

Error cases:

```php
Numbers::copySign(NAN, 5);     // throws DomainException
Numbers::copySign(5, NAN);     // throws DomainException
```

**Use Cases:**

- Implementing mathematical functions that need to preserve sign relationships.
- Working with algorithms that require specific sign control (e.g., coordinate transformations).
- Ensuring consistent sign handling across calculations.

**Note:** Similar to C's `copysign()` function, but with explicit NAN rejection for clarity.

---

## See Also

- **[Floats](Floats.md)** - Float-specific utility methods including `approxEqual()` for approximate comparisons.
- **[Integers](Integers.md)** - Integer-specific utility methods.
- **[Types](Types.md)** - Type checking and comparison utilities.
