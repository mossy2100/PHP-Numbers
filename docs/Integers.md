# Integers

Static utility class for integer arithmetic with overflow detection and number formatting.

---

## Overview

The `Integers` class provides integer arithmetic methods with overflow detection, number theory functions, and
subscript/superscript conversion utilities. This is a static utility class and cannot be instantiated.

### Overflow Detection

In PHP, when integer arithmetic operations exceed `PHP_INT_MAX` or fall below `PHP_INT_MIN`, the result is silently
converted to a float. This can lead to unexpected behavior in calculations that should produce integers.

```php
PHP_INT_MAX + 1;  // Returns a float, not an integer
```

The arithmetic methods in this class detect overflow and throw `OverflowException` instead of silently converting to
float. This is useful when you need to ensure results remain within the integer range or handle overflow explicitly.

---

## Constants

### SUBSCRIPT_CHARACTERS

```php
public const array SUBSCRIPT_CHARACTERS
```

Unicode subscript characters for digits 0-9 and minus sign. Maps ASCII characters to their Unicode subscript
equivalents.

### SUPERSCRIPT_CHARACTERS

```php
public const array SUPERSCRIPT_CHARACTERS
```

Unicode superscript characters for digits 0-9 and minus sign. Maps ASCII characters to their Unicode superscript
equivalents.

---

## Binary Arithmetic Methods

### add()

```php
public static function add(int $a, int $b): int
```

Add two integers with overflow detection.

**Parameters:**

- `$a` (int) - The first integer
- `$b` (int) - The second integer

**Returns:** `int` - The sum of the two integers

**Throws:** `OverflowException` - If the result's magnitude is too large to be represented as an integer.

**Examples:**

```php
Integers::add(5, 3);           // 8
Integers::add(-10, 15);        // 5
Integers::add(PHP_INT_MAX, 1); // throws OverflowException
```

### sub()

```php
public static function sub(int $a, int $b): int
```

Subtract one integer from another with overflow detection.

**Parameters:**

- `$a` (int) - The integer to subtract from
- `$b` (int) - The integer to subtract

**Returns:** `int` - The difference (a - b)

**Throws:** `OverflowException` - If the result's magnitude is too large to be represented as an integer.

**Examples:**

```php
Integers::sub(10, 3);           // 7
Integers::sub(5, -5);           // 10
Integers::sub(PHP_INT_MIN, 1);  // throws OverflowException
```

### mul()

```php
public static function mul(int $a, int $b): int
```

Multiply two integers with overflow detection.

**Parameters:**

- `$a` (int) - The first integer
- `$b` (int) - The second integer

**Returns:** `int` - The product

**Throws:** `OverflowException` - If the result's magnitude is too large to be represented as an integer.

**Examples:**

```php
Integers::mul(6, 7);            // 42
Integers::mul(-3, 4);           // -12
Integers::mul(PHP_INT_MAX, 2);  // throws OverflowException
```

---

## Power Methods

### pow()

```php
public static function pow(int $a, int $b): int
```

Raise one integer to the power of another, returning an integer result or throwing an exception.

**Parameters:**

- `$a` (int) - The base
- `$b` (int) - The exponent

**Returns:** `int` - The result of raising a to the power of b

**Throws:** `DomainException` - If the exponent is negative. This always throws regardless of the base, including `1 ** -1` and
  `(-1) ** -1`, since the result would be a float in every case.
- `OverflowException` - If the result's magnitude is too large to be represented as an integer.

**Examples:**

```php
Integers::pow(2, 10);   // 1024
Integers::pow(5, 0);    // 1
Integers::pow(-2, 3);   // -8
Integers::pow(1, -1);   // throws DomainException (negative exponent)
Integers::pow(-1, -1);  // throws DomainException (negative exponent)
Integers::pow(2, -1);   // throws DomainException (negative exponent)
Integers::pow(10, 100); // throws OverflowException
```

---

## Number Theory Methods

### gcd()

```php
public static function gcd(int ...$nums): int
```

Calculate the greatest common divisor (GCD) of two or more integers using Euclid's algorithm. The GCD is the largest
positive integer that divides all the given numbers without remainder.

**Parameters:**

- `...$nums` (int) - One or more integers

**Returns:** `int` - The greatest common divisor (always non-negative)

**Throws:**

- `BadMethodCallException` - If no arguments are provided
- `OverflowException` - If the true result is `PHP_INT_MIN`'s magnitude (`2^63`), which doesn't fit in an `int`. This
  only happens when `PHP_INT_MIN` is present and every other argument is `0` or also `PHP_INT_MIN` — any other value
  has a smaller magnitude and would reduce the GCD below `2^63`. `PHP_INT_MIN` combined with anything else always
  produces a valid, in-range result (see examples below).

**Examples:**

```php
Integers::gcd(12, 18);      // 6
Integers::gcd(17, 19);      // 1 (coprime)
Integers::gcd(12, 18, 24);  // 6
Integers::gcd(-12, 18);     // 6 (uses absolute values)
Integers::gcd(0, 5);        // 5
Integers::gcd(0, 0);        // 0

// PHP_INT_MIN's magnitude (2^63) has only 2 as a prime factor, so combined with another value the result is always
// a power of two: 2 raised to the lower of 63 and the other value's own power-of-two factor.
Integers::gcd(PHP_INT_MIN, 5);   // 1 (5 is odd)
Integers::gcd(PHP_INT_MIN, 6);   // 2 (6 = 2 * 3)
Integers::gcd(PHP_INT_MIN, 8);   // 8 (8 = 2^3)
Integers::gcd(PHP_INT_MIN);      // throws OverflowException (nothing reduces PHP_INT_MIN's own magnitude)
```

**Behavior:**

- The GCD is always computed using absolute values, so negative inputs are treated as positive
- The GCD of 0 and any number n is |n|
- The GCD of 0 and 0 is 0
- `PHP_INT_MIN` is allowed as an argument (see **Throws** above for the one case where it still overflows)

---

## Conversion Methods

### toSubscript()

```php
public static function toSubscript(int $n): string
```

Convert an integer to Unicode subscript characters.

**Parameters:**

- `$n` (int) - The integer to convert

**Returns:** `string` - The integer as subscript characters

**Examples:**

```php
Integers::toSubscript(123);   // '₁₂₃'
Integers::toSubscript(0);     // '₀'
Integers::toSubscript(-42);   // '₋₄₂'
```

**Use cases:**

- Chemical formulas, e.g. `H₂SO₄`
- Variable subscripts, e.g. `x₁ = 123`

### toSuperscript()

```php
public static function toSuperscript(int $n): string
```

Convert an integer to Unicode superscript characters.

**Parameters:**

- `$n` (int) - The integer to convert

**Returns:** `string` - The integer as superscript characters

**Examples:**

```php
Integers::toSuperscript(123);   // '¹²³'
Integers::toSuperscript(0);     // '⁰'
Integers::toSuperscript(-42);   // '⁻⁴²'
```

**Use cases:**

- Exponents, e.g. `x²`, `6.02×10²³`.

### isSubscript()

```php
public static function isSubscript(string $s): bool
```

Check if a string is a valid subscript integer representation.

**Parameters:**

- `$s` (string) - The string to check

**Returns:** `bool` - True if the string matches the pattern for a subscript integer

**Examples:**

```php
Integers::isSubscript('₁₂₃');   // true
Integers::isSubscript('₋₄₂');   // true (negative)
Integers::isSubscript('₀');     // true
Integers::isSubscript('123');   // false (regular digits)
Integers::isSubscript('¹²³');   // false (superscript)
Integers::isSubscript('');      // false (empty)
Integers::isSubscript('₋');     // false (just minus sign)
```

### isSuperscript()

```php
public static function isSuperscript(string $s): bool
```

Check if a string is a valid superscript integer representation.

**Parameters:**

- `$s` (string) - The string to check

**Returns:** `bool` - True if the string matches the pattern for a superscript integer

**Examples:**

```php
Integers::isSuperscript('¹²³');   // true
Integers::isSuperscript('⁻⁴²');   // true (negative)
Integers::isSuperscript('⁰');     // true
Integers::isSuperscript('123');   // false (regular digits)
Integers::isSuperscript('₁₂₃');   // false (subscript)
Integers::isSuperscript('');      // false (empty)
Integers::isSuperscript('⁻');     // false (just minus sign)
```

### fromSubscript()

```php
public static function fromSubscript(string $s): int
```

Convert a string of Unicode subscript characters to an integer.

**Parameters:**

- `$s` (string) - The subscript string to convert

**Returns:** `int` - The integer value

**Throws:**

- [`FormatException`](Exceptions/FormatException.md) - If the string contains invalid subscript characters, or
  doesn't represent an integer (e.g. it's empty, or just a minus sign with no digits).
- `OverflowException` - If the value is outside the valid range for `int` (`PHP_INT_MIN` to `PHP_INT_MAX`).

**Examples:**

```php
Integers::fromSubscript('₁₂₃');   // 123
Integers::fromSubscript('₋₄₂');   // -42
Integers::fromSubscript('₀');     // 0
Integers::fromSubscript('₅');     // 5

Integers::fromSubscript('123');   // throws FormatException
Integers::fromSubscript('¹²³');   // throws FormatException
Integers::fromSubscript('');      // throws FormatException
Integers::fromSubscript('₋');     // throws FormatException (minus sign with no digits)
Integers::fromSubscript('₉₉₉₉₉₉₉₉₉₉₉₉₉₉₉₉₉₉₉₉'); // throws OverflowException
```

**Note:** Use `isSubscript()` to validate input before calling this method if you need to handle invalid input
gracefully.

### fromSuperscript()

```php
public static function fromSuperscript(string $s): int
```

Convert a string of Unicode superscript characters to an integer.

**Parameters:**

- `$s` (string) - The superscript string to convert

**Returns:** `int` - The integer value

**Throws:**

- [`FormatException`](Exceptions/FormatException.md) - If the string contains invalid superscript characters, or
  doesn't represent an integer (e.g. it's empty, or just a minus sign with no digits).
- `OverflowException` - If the value is outside the valid range for `int` (`PHP_INT_MIN` to `PHP_INT_MAX`).

**Examples:**

```php
Integers::fromSuperscript('¹²³');   // 123
Integers::fromSuperscript('⁻⁴²');   // -42
Integers::fromSuperscript('⁰');     // 0
Integers::fromSuperscript('⁵');     // 5

Integers::fromSuperscript('123');   // throws FormatException
Integers::fromSuperscript('₁₂₃');   // throws FormatException
Integers::fromSuperscript('');      // throws FormatException
Integers::fromSuperscript('⁻');     // throws FormatException (minus sign with no digits)
Integers::fromSuperscript('⁹⁹⁹⁹⁹⁹⁹⁹⁹⁹⁹⁹⁹⁹⁹⁹⁹⁹⁹'); // throws OverflowException
```

**Note:** Use `isSuperscript()` to validate input before calling this method if you need to handle invalid input
gracefully.

---

## See Also

- **[Floats](Floats.md)** - Float utility methods
- **[Numbers](Numbers.md)** - General number utilities
