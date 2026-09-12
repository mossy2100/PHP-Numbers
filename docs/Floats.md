# Floats

Static utility class for working with floating-point numbers in PHP.

---

## Overview

The `Floats` class provides tools for comparison, conversion, navigation, random generation, and handling of IEEE-754
special values. This is a static utility class and cannot be instantiated.

### Comparison Issues

Direct comparison of floats using `===` often fails due to precision loss in calculations:

```php
0.1 + 0.2 === 0.3;  // false (!)
```

The `approxEqual()` method provides reliable approximate comparison with configurable tolerance.

### IEEE-754 Special Values

The IEEE-754 standard defines several special values with unique properties:

- **-0.0 and +0.0**: Distinct values that compare as equal (`-0.0 === 0.0` returns `true`), but have different binary
  representations and can produce different results in certain operations (e.g., `1.0 / -0.0` returns `-INF`).
- **INF and -INF**: Positive and negative infinity, representing values too large to represent.
- **NAN**: 'Not a Number', the result of undefined operations (e.g., `0.0 / 0.0`, `sqrt(-1)`).

Several methods are provided to facilitate working with these values: `isNegativeZero()`, `isPositiveZero()`,
`isSpecial()`, and `normalizeZero()`.

### Floats and Integers

Converting floats to integers can lose precision. The `toInt()` method provides safe, lossless conversion when possible,
throwing `DomainException` if conversion would lose precision.

Some floats represent whole numbers numerically without necessarily being safely convertible: `isInt()` checks this with
no range restriction, `isSafeInt()` additionally requires the value to be within the safe integer range
(±(2<sup>53</sup> - 1)) where every integer is uniquely representable, and `isApproxInt()` allows for a tolerance to
absorb accumulated floating-point error.

### Navigating the Float Space

The `next()` and `previous()` methods allow traversal of the IEEE-754 number line, useful for testing edge cases and
understanding float precision.

### Random Float Generation

Two methods provide random floats for different use cases:

- `rand()` generates random floats within a specified range (or the full float space by default) using IEEE-754
  component assembly.
- `randUniform()` generates uniformly distributed values within specific bounds using linear interpolation with
  automatic ULP-based step calculation.

### IEEE-754 Component Access

Two methods provide direct access to IEEE-754 double-precision components:

- `disassemble()` extracts sign, exponent, and fraction from a float.
- `assemble()` constructs a float from sign, exponent, and fraction components.

---

## Constants

### DEFAULT_RELATIVE_TOLERANCE

```php
public const float DEFAULT_RELATIVE_TOLERANCE = 1e-9;
```

The default relative tolerance used by `approxEqual()` and `approxCompare()`.

### DEFAULT_ABSOLUTE_TOLERANCE

```php
public const float DEFAULT_ABSOLUTE_TOLERANCE = PHP_FLOAT_EPSILON;
```

The default absolute tolerance used by `approxEqual()` and `approxCompare()`.

### MAX_SAFE_INT

```php
public const int MAX_SAFE_INT = 2 ** 53 - 1;  // 9007199254740991
```

Maximum integer that can be safely represented as a float and round-tripped without collision (2<sup>53</sup> - 1).
Beyond this value, multiple consecutive integers can convert to the same float. Equivalent to JavaScript's
`Number.MAX_SAFE_INTEGER`. See `isSafeInt()` for additional explanation.

---

## Inspection Methods

### isNegativeZero()

```php
public static function isNegativeZero(float $value): bool
```

Determines if a floating-point number is negative zero (-0.0).

**Parameters:**

- `$value` (float) - The floating-point number to check

**Returns:** `bool` - Returns `true` if the value is negative zero (-0.0), `false` otherwise

**Examples:**

```php
Floats::isNegativeZero(-0.0);  // true
Floats::isNegativeZero(0.0);   // false
Floats::isNegativeZero(-1.0);  // false
```

### isPositiveZero()

```php
public static function isPositiveZero(float $value): bool
```

Determines if a floating-point number is positive zero (+0.0).

**Parameters:**

- `$value` (float) - The floating-point number to check

**Returns:** `bool` - Returns `true` if the value is positive zero (+0.0), `false` otherwise

**Examples:**

```php
Floats::isPositiveZero(0.0);   // true
Floats::isPositiveZero(-0.0);  // false
Floats::isPositiveZero(1.0);   // false
```

### isNegative()

```php
public static function isNegative(float $value): bool
```

Check if a floating-point number is negative. This method considers -0.0 as negative (unlike the simple comparison
`$value < 0`).

**Parameters:**

- `$value` (float) - The value to check

**Returns:** `bool` - Returns `true` for -0.0, -INF, and negative values; `false` for +0.0, INF, NAN, and positive values

**Examples:**

```php
Floats::isNegative(-1.0);   // true
Floats::isNegative(-0.0);   // true
Floats::isNegative(-INF);   // true
Floats::isNegative(0.0);    // false
Floats::isNegative(1.0);    // false
Floats::isNegative(NAN);    // false
```

**Note:** NAN is considered neither positive nor negative.

### isPositive()

```php
public static function isPositive(float $value): bool
```

Check if a floating-point number is positive. This method considers +0.0 as positive.

**Parameters:**

- `$value` (float) - The value to check

**Returns:** `bool` - Returns `true` for +0.0, INF, and positive values; `false` for -0.0, -INF, NAN, and negative values

**Examples:**

```php
Floats::isPositive(1.0);    // true
Floats::isPositive(0.0);    // true
Floats::isPositive(INF);    // true
Floats::isPositive(-0.0);   // false
Floats::isPositive(-1.0);   // false
Floats::isPositive(NAN);    // false
```

**Note:** NAN is considered neither positive nor negative.

### isSpecial()

```php
public static function isSpecial(float $value): bool
```

Check if a float is one of the special IEEE-754 values: NAN, -0.0, +INF, or -INF. Note that +0.0 is not considered a
special value.

**Parameters:**

- `$value` (float) - The value to check

**Returns:** `bool` - Returns `true` if the value is NAN, -0.0, +INF, or -INF; `false` otherwise

**Examples:**

```php
Floats::isSpecial(NAN);    // true
Floats::isSpecial(-0.0);   // true
Floats::isSpecial(INF);    // true
Floats::isSpecial(-INF);   // true
Floats::isSpecial(0.0);    // false
Floats::isSpecial(1.0);    // false
Floats::isSpecial(-42.5);  // false
```

**Use Case:** Useful for validation or special handling of edge cases in numerical computations.

---

### getExponent()

```php
public static function getExponent(float $value): int
```

Get the base-10 exponent of a float, as if it were written in normalized scientific notation (a single non-zero digit
before the decimal point). Computed via `floor(log10(abs($value)))`, then corrected for `log10()` rounding error at
exact powers of 10 (e.g. some platforms return `log10(1000)` as `2.9999999999999996` rather than exactly `3.0`, which
would otherwise throw off the result by one).

**Parameters:**

- `$value` (float) - The value to get the exponent of. Must be finite.

**Returns:** `int` - The exponent. Returns `0` for a value of `0.0`.

**Throws:** `DomainException` - If `$value` is non-finite.

**Examples:**

```php
Floats::getExponent(5.0);      // 0
Floats::getExponent(1234.0);   // 3
Floats::getExponent(-1234.0);  // 3 (sign-independent)
Floats::getExponent(0.001);    // -3
Floats::getExponent(1000.0);   // 3 (exact power of 10)
Floats::getExponent(0.0);      // 0
```

**Use Case:** For magnitude-based decisions (e.g. choosing a unit prefix, or how many significant digits are
meaningful).

---

## Comparison Methods

### approxEqual()

```php
public static function approxEqual(
    float $a,
    float $b,
    float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
    float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
): bool
```

Check if two floats are approximately equal using combined absolute and relative tolerance. This is the recommended way
to compare floating-point numbers for equality, as direct comparison (`===`) can fail due to precision issues.

This method mirrors [Python's `math.isclose()` function](https://docs.python.org/3/library/math.html#math.isclose),
which uses the following algorithm:

1. First checks if values are exactly equal (handles identical values efficiently).
2. Checks absolute tolerance (useful for comparisons near zero).
3. If exceeded, checks relative tolerance (scales with magnitude).

**Parameters:**

- `$a` (float) - The first float
- `$b` (float) - The second float
- `$relTol` (float) - The maximum allowed relative difference (default: `1e-9`)
- `$absTol` (float) - The maximum allowed absolute difference (default: `PHP_FLOAT_EPSILON`, which is ~2.22e-16)

**Returns:** `bool` - Returns `true` if the floats are approximately equal, `false` otherwise

**Throws:** `DomainException` - If either tolerance is negative

**Examples:**

Basic usage with combined tolerance:

```php
// Direct float comparison can fail due to precision issues
0.1 + 0.2 === 0.3;  // false (!)

// Use approxEqual instead
Floats::approxEqual(0.1 + 0.2, 0.3);  // true

// Identical values (fast path)
Floats::approxEqual(1.0, 1.0);  // true

// Near zero - absolute tolerance applies
Floats::approxEqual(1e-20, 2e-20);  // true (within PHP_FLOAT_EPSILON)

// Larger values - relative tolerance applies
Floats::approxEqual(1000000.0, 1000000.1, 1e-6);  // true
Floats::approxEqual(1.0, 1.1, 1e-6);  // false
```

With custom tolerances:

```php
// Tighter absolute tolerance
Floats::approxEqual(1e-20, 2e-20, 1e-9, 0.0);  // false

// Looser relative tolerance
Floats::approxEqual(100.0, 101.0, 0.02);  // true (1% difference, within 2%)

// Custom both
Floats::approxEqual(0.001, 0.00105, 0.01, 1e-4);  // true
```

Special values:

```php
// Handles positive and negative zero
Floats::approxEqual(0.0, -0.0);   // true

// Infinities use exact equality
Floats::approxEqual(INF, INF);    // true
Floats::approxEqual(-INF, -INF);  // true
Floats::approxEqual(INF, -INF);   // false

// NAN is never equal to anything
Floats::approxEqual(NAN, NAN);    // false
Floats::approxEqual(NAN, 0.0);    // false
```

**Behavior:**

- Handles NAN by returning `false` (NAN is never equal to anything).
- Handles infinities using exact equality (`INF === INF`, `-INF === -INF`).
- First checks exact equality (`$a === $b`) as a fast path.
- Then checks absolute tolerance: `abs($a - $b) <= $absTol`.
- Finally checks relative tolerance: `abs($a - $b) <= $relTol * max(abs($a), abs($b))`.
- **Symmetric**: `approxEqual($a, $b)` equals `approxEqual($b, $a)`.

**Choosing Tolerances:**

- **Defaults**: Work well for most general-purpose comparisons.
- **Absolute tolerance**: Use `PHP_FLOAT_EPSILON` (default) for values near zero, or custom values for domain-specific
  precision.
- **Relative tolerance**: Use `1e-9` (default) for tight comparisons, `1e-6` for looser comparisons.

If you want to compare values by relative tolerance only, set `$absTol` to zero.

If you want to compare values by absolute tolerance only, set `$relTol` to zero.

If in doubt, write tests that reflect expected/typical usage in your application, and adjust tolerances for optimal
results.

**Use Cases:**

- Comparing results of floating-point calculations
- Unit testing numerical code
- Checking convergence in iterative algorithms
- Scientific and engineering calculations

**See Also:**

- `approxCompare()` - Three-way comparison with approximate equality

### approxCompare()

```php
public static function approxCompare(
    float $a,
    float $b,
    float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
    float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
): int
```

Three-way comparison of two floats with approximate equality support. Returns an integer indicating whether the first
float is less than, equal to (within tolerance), or greater than the second float.

**Parameters:**

- `$a` (float) - The first float
- `$b` (float) - The second float
- `$relTol` (float) - The maximum allowed relative difference (default: `1e-9`)
- `$absTol` (float) - The maximum allowed absolute difference (default: `PHP_FLOAT_EPSILON`)

**Returns:** `int` - Returns exactly `-1` if `$a < $b`, `0` if approximately equal, `1` if `$a > $b`

**Throws:** `DomainException` - If either float is NAN, or either tolerance is negative

**Examples:**

```php
// Basic three-way comparison
Floats::approxCompare(1.0, 2.0);  // -1 (less than)
Floats::approxCompare(2.0, 1.0);  // 1 (greater than)
Floats::approxCompare(1.0, 1.0);  // 0 (equal)

// Approximate equality with default tolerances
Floats::approxCompare(1.0, 1.0 + 1e-11);  // 0 (within tolerance)
Floats::approxCompare(1.0, 1.1);          // -1 (exceeds tolerance)

// Combined absolute and relative tolerance
Floats::approxCompare(1000000.0, 1000000.1, 1e-6);  // 0 (within relative tolerance)
Floats::approxCompare(1.0, 1.1, 1e-6);              // -1 (exceeds relative tolerance)

// Custom tolerances
Floats::approxCompare(1.0, 1.0 + 1e-11, 1e-9, 1e-10);  // 0
Floats::approxCompare(1.0, 1.0 + 1e-9, 1e-9, 1e-10);   // -1

// Handles precision issues
Floats::approxCompare(0.1 + 0.2, 0.3);  // 0 (approximately equal)

// Infinities use exact equality via approxEqual()
Floats::approxCompare(INF, INF);      // 0 (equal)
Floats::approxCompare(INF, 1000.0);   // 1 (INF > finite)
Floats::approxCompare(0.0, -0.0);     // 0 (equal)

// NAN throws DomainException
Floats::approxCompare(NAN, 1.0);      // throws DomainException
```

**Behavior:**

- Throws `DomainException` if either argument is NAN (NAN cannot be meaningfully compared).
- First checks approximate equality using `approxEqual()` with the specified tolerances.
- If approximately equal, returns `0`.
- Otherwise uses spaceship operator (`<=>`) to determine ordering, normalized to exactly -1 or 1 using
  `Numbers::sign()`.
- Infinities are handled by `approxEqual()` using exact equality.

**Use Cases:**

- Implementing `compare()` methods in custom classes (e.g., `Comparable` trait)
- Sorting floats with epsilon tolerance
- Building comparison logic that needs three-way results
- Switch statements based on comparison results
- Less-than or greater-than functions with tolerances

**Example Usage in Sorting:**

```php
$values = [1.0000001, 1.0, 0.9999999];

usort($values, function($a, $b) {
    return Floats::approxCompare($a, $b, 1e-6);
});

// Result: All three values may be considered equal with tolerance 1e-6
```

**See Also:**

- `approxEqual()` - Two-way approximate equality check

---

## Transformation Methods

### normalizeZero()

```php
public static function normalizeZero(float $value): float
```

Normalizes negative zero to positive zero. This can be used to avoid surprising results from certain operations where
the distinction between -0.0 and +0.0 matters.

**Parameters:**

- `$value` (float) - The floating-point number to normalize

**Returns:** `float` - Returns `0.0` if the input is `-0.0`, otherwise returns the value unchanged

**Examples:**

```php
Floats::normalizeZero(-0.0);  // 0.0
Floats::normalizeZero(0.0);   // 0.0
Floats::normalizeZero(-1.5);  // -1.5
Floats::normalizeZero(2.5);   // 2.5
```

**Use Case:** When you want consistent behavior regardless of whether a zero is positive or negative, especially in
comparisons or output formatting.

### trunc()

```php
public static function trunc(float $value): float
```

Truncate a float towards zero (remove the fractional part). This is equivalent to `floor()` for positive numbers and
`ceil()` for negative numbers. Unlike casting to int, this method handles values outside PHP's integer range.

**Parameters:**

- `$value` (float) - The value to truncate

**Returns:** `float` - The truncated value (integer part towards zero)

**Examples:**

```php
// Positive values - same as floor()
Floats::trunc(3.7);     // 3.0
Floats::trunc(3.2);     // 3.0
Floats::trunc(3.0);     // 3.0

// Negative values - different from floor()
Floats::trunc(-3.7);    // -3.0 (floor would give -4.0)
Floats::trunc(-3.2);    // -3.0 (floor would give -4.0)
Floats::trunc(-3.0);    // -3.0

// Values between -1 and 1
Floats::trunc(0.9);     // 0.0
Floats::trunc(-0.9);    // 0.0

// Zero values
Floats::trunc(0.0);     // 0.0
Floats::trunc(-0.0);    // 0.0

// Non-finite values pass through unchanged
Floats::trunc(INF);     // INF
Floats::trunc(-INF);    // -INF
Floats::trunc(NAN);     // NAN
```

**Comparison with floor():**

| Value | `Floats::trunc()` | `floor()` |
| ----- | ----------------- | --------- |
| 3.7   | 3.0               | 3.0       |
| -3.7  | -3.0              | -4.0      |
| 0.9   | 0.0               | 0.0       |
| -0.9  | 0.0               | -1.0      |

**Identity Property:**

The `trunc()` and `frac()` methods satisfy the identity: `x = trunc(x) + frac(x)`

```php
$x = -3.7;
Floats::trunc($x) + Floats::frac($x);  // -3.0 + (-0.7) = -3.7
```

**Use Cases:**

- Extracting the integer part of a number towards zero
- Implementing mathematical functions that require truncation semantics
- Working with values too large for PHP's integer type

**See Also:**

- `frac()` - Get the fractional part (satisfies x = trunc(x) + frac(x))
- `isInt()` - Check if a float numerically represents an integer

### frac()

```php
public static function frac(float $value): float
```

Return the fractional part of a float. This method satisfies the identity: `x = trunc(x) + frac(x)`. For negative
values, the result is also negative.

**Parameters:**

- `$value` (float) - The value to get the fractional part of

**Returns:** `float` - The fractional part. Returns `0.0` for ±INF (infinity has no fractional part). Returns `NAN` for NAN input.

**Examples:**

```php
// Positive values
Floats::frac(3.7);      // 0.7
Floats::frac(3.2);      // 0.2
Floats::frac(3.0);      // 0.0
Floats::frac(0.9);      // 0.9
Floats::frac(100.999);  // 0.999

// Negative values - result is also negative
Floats::frac(-3.7);     // -0.7
Floats::frac(-3.2);     // -0.2
Floats::frac(-3.0);     // 0.0
Floats::frac(-0.9);     // -0.9

// Zero values
Floats::frac(0.0);      // 0.0
Floats::frac(-0.0);     // 0.0

// Infinity has no fractional part
Floats::frac(INF);      // 0.0
Floats::frac(-INF);     // 0.0
Floats::frac(NAN);      // NAN
```

**Identity Property:**

```php
$values = [3.7, -3.7, 0.5, -0.5, 100.999, -100.999];
foreach ($values as $x) {
    $x === Floats::trunc($x) + Floats::frac($x);  // true for all
}
```

**Use Cases:**

- Extracting the decimal portion of a number
- Implementing modular arithmetic with floats
- Signal processing and waveform generation
- Checking if a value has a fractional component

**See Also:**

- `trunc()` - Get the integer part towards zero

### wrap()

```php
public static function wrap(float $value, float $unitsPerTurn = M_TAU, bool $signed = true): float
```

Wrap a value into a standard range, typically used for normalizing angles or other cyclic quantities. The method reduces
the value modulo the period and adjusts it to fit within the specified range.

**Parameters:**

- `$value` (float) - The value to wrap
- `$unitsPerTurn` (float) - The period/range size (default: `OceanMoon\Core\M_TAU` for radians)
- `$signed` (bool) - If `true`, use signed range; if `false`, use unsigned range (default: `true`)

**Returns:** `float` - The wrapped value within the specified range

**Throws:** `DomainException` - If `$unitsPerTurn` is not finite and positive.

**Behavior:**

The range depends on the `$signed` parameter:

| Mode               | Range                   | Lower Bound | Upper Bound |
| ------------------ | ----------------------- | ----------- | ----------- |
| Signed (`true`)    | `(-period/2, period/2]` | Excluded    | Included    |
| Unsigned (`false`) | `[0, period)`           | Included    | Excluded    |

Note that each range has one boundary excluded and one included:

- **Signed**: The lower bound is excluded, upper bound is included. For degrees: `(-180°, 180°]`.
- **Unsigned**: The lower bound is included, upper bound is excluded. For degrees: `[0°, 360°)`.

The method also normalizes `-0.0` to `0.0` to avoid unexpected behavior.

**Examples with Degrees (360):**

```php
// Values already in range remain unchanged
Floats::wrap(0.0, 360.0);      // 0.0
Floats::wrap(45.0, 360.0);     // 45.0
Floats::wrap(-90.0, 360.0);    // -90.0

// Values outside range are wrapped
Floats::wrap(270.0, 360.0);    // -90.0 (wraps to negative)
Floats::wrap(450.0, 360.0);    // 90.0
Floats::wrap(-270.0, 360.0);   // 90.0 (wraps to positive)
Floats::wrap(720.0, 360.0);    // 0.0 (multiple rotations)

// Boundary behavior (signed)
Floats::wrap(180.0, 360.0);    // 180.0 (upper bound included)
Floats::wrap(-180.0, 360.0);   // 180.0 (lower bound excluded, wraps to upper)

// Unsigned range [0°, 360°)
Floats::wrap(270.0, 360.0, signed: false);   // 270.0 (in range)
Floats::wrap(-90.0, 360.0, signed: false);   // 270.0 (negative wraps to positive)
Floats::wrap(360.0, 360.0, signed: false);   // 0.0 (upper bound excluded)
Floats::wrap(450.0, 360.0, signed: false);   // 90.0
```

**Examples with Radians (default):**

```php
// Signed range (-π, π] - default
Floats::wrap(0.0);                  // 0.0
Floats::wrap(M_PI);                 // π (upper bound included)
Floats::wrap(-M_PI);                // π (lower bound excluded, wraps to upper)
Floats::wrap(3 * M_PI / 2);         // -π/2
Floats::wrap(M_TAU);                // 0.0

// Unsigned range [0, τ)
Floats::wrap(M_PI, signed: false);            // π
Floats::wrap(-M_PI / 2, signed: false);       // 3π/2
Floats::wrap(M_TAU, signed: false);           // 0.0
```

**Examples with Other Units:**

```php
// Gradians (400 per turn)
Floats::wrap(300.0, 400.0);    // -100.0
Floats::wrap(500.0, 400.0);    // 100.0

// Turns (1 per turn)
Floats::wrap(0.75, 1.0);       // -0.25
Floats::wrap(1.5, 1.0);        // 0.5

// Hours (24-hour clock, unsigned)
Floats::wrap(25.0, 24.0, signed: false);   // 1.0
Floats::wrap(-3.0, 24.0, signed: false);   // 21.0 (3 hours before midnight)
Floats::wrap(50.0, 24.0, signed: false);   // 2.0
```

**Use Cases:**

- Normalizing angles after arithmetic operations
- Compass bearings and navigation calculations
- Clock/time arithmetic (hours, minutes)
- Periodic/cyclic signal processing
- Game development (rotation, direction)
- Any domain with wraparound behavior

**See Also:**

- `M_TAU` - The circle constant τ = 2π, default period for radians (`OceanMoon\Core\M_TAU`)
- `normalizeZero()` - Used internally to handle negative zero

---

## Conversion Methods

### toHex()

```php
public static function toHex(float $value): string
```

Convert a float to a unique 16-character hexadecimal string representation. Every possible float value produces a unique
hex string, making this method ideal for hashing or keying floats in collections.

**Note:** The method works for NAN, but, be aware, the NAN as defined by IEEE doesn't technically have a unique hex
representation; in fact, for 64-bit IEEE floats, 2<sup>53</sup> - 2 bit patterns mean NAN. This method will return the
hex representation of NAN used by PHP ('7ff8000000000000'). Also see notes for `bitsToFloat()`.

**Parameters:**

- `$value` (float) - The float to convert

**Returns:** `string` - A 16-character hexadecimal string representing the binary representation of the float

**Throws:** `RuntimeException` - If the system is not 64-bit

**Examples:**

```php
$hex1 = Floats::toHex(1.0);
$hex2 = Floats::toHex(2.0);
$hex1 !== $hex2;  // true - different values produce different hex strings

// Distinguishes between -0.0 and +0.0
Floats::toHex(-0.0) !== Floats::toHex(0.0);  // true

// Even very close values produce different hex strings
$a = 1.0;
$b = 1.0 + PHP_FLOAT_EPSILON;
Floats::toHex($a) !== Floats::toHex($b);  // true
```

**Advantages over string conversion:**

- **Uniqueness**: Unlike casting to string or using `sprintf()`, every distinct float value (including -0.0 vs +0.0)
  produces a unique hex string.
- **Consistency**: Always produces exactly 16 characters.
- **Precision**: Preserves the exact binary representation of the float.

### format()

```php
public static function format(
    float $value,
    int $precision = 6,
    bool $trimZeros = true,
    FloatFormat $format = FloatFormat::Auto,
    ExponentFormat $expFormat = ExponentFormat::UnicodeMath,
    RoundingMode $roundingMode = RoundingMode::HalfAwayFromZero
): string
```

Format a float as a string with control over precision, notation, exponent style, and rounding. A richer alternative to
`sprintf()` with built-in support for Unicode scientific notation (e.g. `1.50×10³` instead of `1.50e+3`).

NAN and ±INF are returned as their default PHP string representations (`'NAN'`, `'INF'`, `'-INF'`), regardless of the
other parameters.

`FloatFormat::Auto` will determine which of `FixedPoint` and `Scientific` produces the more readable and useful output. See [FloatFormat::Auto](Enums/FloatFormat.md#auto) for more detail.

**Parameters:**

- `$value` (float) - The numeric value to format.
- `$precision` (int) - Maximum number of decimal places (fixed point notation) or significant digits (scientific
  notation) to include. Default `6`.
- `$trimZeros` (bool) - If `true` (default), trailing zeros — and, if necessary, a trailing decimal point — are removed.
  If `false`, all digits from `$precision` are preserved.
- `$format` (`FloatFormat`) - Selects the notation. Default `FloatFormat::Auto`.

| Case                      | Description                                                          |
| ------------------------- | -------------------------------------------------------------------- |
| `FloatFormat::FixedPoint` | Does not include an exponential part.                                |
| `FloatFormat::Scientific` | Always includes an exponential part.                                 |
| `FloatFormat::Auto`       | Whichever of the above produces the more useful string. **Default.** |

- `$expFormat` (`ExponentFormat`) - Controls how the exponential part is rendered when `FloatFormat` is `Scientific`. Default `ExponentFormat::UnicodeMath`.

| Case                              | Example                  |
| --------------------------------- | ------------------------ |
| `ExponentFormat::AsciiLowerCaseE` | `e+42`                   |
| `ExponentFormat::AsciiUpperCaseE` | `E+42`                   |
| `ExponentFormat::AsciiMath`       | `*10^42`                 |
| `ExponentFormat::UnicodeMath`     | `×10⁴²` **Default.**     |
| `ExponentFormat::HtmlMath`        | `&times;10<sup>42</sup>` |

- `$roundingMode` (`RoundingMode`) - The rounding mode to use. Default `RoundingMode::HalfAwayFromZero`, matching
  `round()`, `Rational::round()`, and `Complex::round()`, rather than `sprintf()`'s round-half-to-even behavior.

**Returns:** `string` - The formatted value string.

**Throws:** `DomainException` - If `$precision` is outside the valid range: 0–17 for `FixedPoint`, 1–17 for `Scientific`/`Auto`.

**Examples:**

```php
// Default: Auto notation, trims trailing zeros.
Floats::format(1234.5);                                      // "1234.5"
Floats::format(5.0);                                          // "5"

// Fixed-point, with and without trimming.
Floats::format(5.0, precision: 2, format: FloatFormat::FixedPoint);                 // "5"
Floats::format(5.0, precision: 2, trimZeros: false, format: FloatFormat::FixedPoint); // "5.00"

// Scientific notation, Unicode math (default).
Floats::format(1500.0, precision: 2, format: FloatFormat::Scientific);  // "1.5×10³"
Floats::format(0.0025, precision: 2, format: FloatFormat::Scientific);  // "2.5×10⁻³"

// Scientific notation, ASCII 'e'.
Floats::format(1500.0, precision: 2, format: FloatFormat::Scientific, expFormat: ExponentFormat::AsciiLowerCaseE);
// "1.5e+3"

// Auto prefers scientific notation for small numbers with excessive leading zeros.
Floats::format(0.0001234);   // "1.234×10⁻⁴"

// -0.0 is normalized to 0.
Floats::format(-0.0);        // "0"

// NAN/±INF pass through as PHP's own string representations, regardless of other parameters.
Floats::format(NAN);         // "NAN"
Floats::format(INF);         // "INF"

// Rounding mode affects ties.
Floats::format(0.5, precision: 0, format: FloatFormat::FixedPoint); // "1" (HalfAwayFromZero, default)
Floats::format(0.5, precision: 0, format: FloatFormat::FixedPoint, roundingMode: RoundingMode::HalfEven); // "0"
```

---

## Integer Methods

### isInt()

```php
public static function isInt(float $value): bool
```

Check if the float represents an integer numerically - i.e. it has no fractional part - with no restriction on
magnitude. This is the most permissive of the three integer-checking methods; see `isSafeInt()` and `isApproxInt()` for
narrower checks.

**Parameters:**

- `$value` (float) - The value to check

**Returns:** `bool` - `true` if the value is numerically an integer, `false` otherwise

**Behavior:**

- Checks two conditions: `is_finite($value) && floor($value) === $value`.
- Returns `true` for whole numbers of any magnitude, including values far beyond `MAX_SAFE_INT`.
- Returns `false` for fractional values.
- Returns `false` for non-finite values (NAN, ±INF).
- Handles negative zero (-0.0) as an integer.

**Examples:**

```php
Floats::isInt(0.0);      // true
Floats::isInt(42.0);     // true
Floats::isInt(-0.0);     // true
Floats::isInt(1e20);     // true (still numerically a whole number, far beyond MAX_SAFE_INT)

Floats::isInt(0.5);      // false
Floats::isInt(1.1);      // false

Floats::isInt(INF);      // false
Floats::isInt(-INF);     // false
Floats::isInt(NAN);      // false
```

**Use Cases:**

- Checking whether a computed float can be treated as a whole number, regardless of magnitude
- Building block for `isSafeInt()` and `isApproxInt()`

**See Also:**

- `isSafeInt()` - Also requires the value to be within the safe integer range
- `isApproxInt()` - Allows a tolerance for accumulated floating-point error
- `toInt()` - Convert a float to an int losslessly

### isSafeInt()

```php
public static function isSafeInt(float $value): bool
```

Check if a float value is exactly representable as an integer, and safely round-trippable. Returns `true` for finite
integers within IEEE-754 double's safe integer range (±`MAX_SAFE_INT`, i.e. ±(2<sup>53</sup> - 1)).

**Parameters:**

- `$value` (float) - The value to check

**Returns:** `bool` - Returns `true` if the value represents a safe integer, `false` otherwise

**Behavior:**

- Checks two conditions: `isInt($value) && abs($value) <= MAX_SAFE_INT`.
- Returns `true` for whole numbers within the safe range.
- Returns `false` for fractional values.
- Returns `false` for values beyond ±(2<sup>53</sup> - 1), **including 2<sup>53</sup> itself**.
- Returns `false` for non-finite values (NAN, ±INF).
- Handles negative zero (-0.0) as a safe integer.

**Examples:**

```php
// Whole numbers within the safe range
Floats::isSafeInt(0.0);      // true
Floats::isSafeInt(1.0);      // true
Floats::isSafeInt(-42.0);    // true
Floats::isSafeInt(1000000.0);  // true

// Fractional values
Floats::isSafeInt(0.5);      // false
Floats::isSafeInt(1.1);      // false
Floats::isSafeInt(-3.14);    // false

// Negative zero is safe
Floats::isSafeInt(-0.0);     // true

// At the boundary
Floats::isSafeInt((float)((1 << 53) - 1));  // true (2^53 - 1, MAX_SAFE_INT)
Floats::isSafeInt((float)(1 << 53));        // false (2^53, exceeds MAX_SAFE_INT)

// Beyond the boundary
Floats::isSafeInt((float)(1 << 54));   // false
Floats::isSafeInt(1e20);               // false

// Non-finite values
Floats::isSafeInt(INF);      // false
Floats::isSafeInt(-INF);     // false
Floats::isSafeInt(NAN);      // false
```

**Why is 2<sup>53</sup> itself excluded?**

IEEE-754 doubles use 52 bits for the fraction plus 1 implicit bit, giving 53 bits of precision. This means consecutive
integers can be exactly represented up to and including 2<sup>53</sup>. However, 2<sup>53</sup> + 1 rounds down to
2<sup>53</sup> if converted to a float, so two distinct integers would collide on the same float. This is true for any
integers greater than 2<sup>53</sup> - 1. `MAX_SAFE_INT` is therefore set to 2<sup>53</sup> - 1, matching JavaScript's
`Number.isSafeInteger()` semantics:

```php
$maxSafe = (float)((1 << 53) - 1);  // 9007199254740991.0
Floats::isSafeInt($maxSafe);        // true

$unsafe = (float)(1 << 53);         // 9007199254740992.0 - still exactly representable...
Floats::isSafeInt($unsafe);         // ...but false, since $unsafe + 1 would collide with it
```

**Comparison with toInt():**

Both methods are concerned with integer representation, but serve different purposes:

| Method        | Purpose                   | Range                                               | Return           |
| ------------- | ------------------------- | --------------------------------------------------- | ---------------- |
| `isSafeInt()` | Check safe representation | ±(2<sup>53</sup> - 1) (float's safe integer range)  | `bool`           |
| `toInt()`     | Lossless conversion       | -2<sup>63</sup> to 2<sup>63</sup>-1 (PHP int range) | `int`, or throws |

For small integers, both agree:

```php
$value = 42.0;
Floats::isSafeInt($value);  // true
Floats::toInt($value);      // 42
```

**Use Cases:**

- Validating that a float represents a whole number that can round-trip safely
- Optimizing arithmetic by detecting when float → int conversion is safe
- Determining when to use integer math vs float math
- Error checking in numerical algorithms

**See Also:**

- `isInt()` - Check numeric integrality with no range restriction
- `isApproxInt()` - Check if a float is approximately an integer
- `toInt()` - Convert float to int losslessly
- `ulp()` - Calculate the spacing between adjacent floats

### isApproxInt()

```php
public static function isApproxInt(
    float $value,
    float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
    float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
): bool
```

Check if a float value is approximately an integer (within tolerance). Unlike `isInt()`, this method allows for small
floating-point errors that may accumulate during calculations.

**Parameters:**

- `$value` (float) - The value to check
- `$relTol` (float) - The maximum allowed relative difference (default: `1e-9`)
- `$absTol` (float) - The maximum allowed absolute difference (default: `PHP_FLOAT_EPSILON`)

**Returns:** `bool` - Returns `true` if the value is approximately an integer, `false` otherwise

**Examples:**

```php
// Exact integers
Floats::isApproxInt(3.0);      // true
Floats::isApproxInt(-42.0);    // true
Floats::isApproxInt(0.0);      // true

// Values very close to integers (within tolerance)
Floats::isApproxInt(3.0000000001);   // true
Floats::isApproxInt(2.9999999999);   // true
Floats::isApproxInt(-5.0000000001);  // true

// Fractional values
Floats::isApproxInt(3.5);      // false
Floats::isApproxInt(0.001);    // false
Floats::isApproxInt(-3.14);    // false

// Useful for logarithm results
Floats::isApproxInt(log10(1000));        // true (result is ~3)
Floats::isApproxInt(log(1000000, 1000)); // true (result is ~2)
Floats::isApproxInt(log(100, 1000));     // false (result is ~0.667)

// Custom tolerance
Floats::isApproxInt(3.0001, 0.0, 1e-3);  // true (within absolute tolerance)
Floats::isApproxInt(3.0001, 0.0, 1e-5);  // false (exceeds tolerance)

// Non-finite values are never approximately an integer, regardless of tolerance.
Floats::isApproxInt(INF);      // false
Floats::isApproxInt(-INF);     // false
Floats::isApproxInt(NAN);      // false
```

**Use Cases:**

- Checking if a logarithm result is an integer power
- Validating that a calculation result is approximately a whole number
- Filtering values based on "integrality" with tolerance for floating-point errors

**See Also:**

- `isInt()` - For exact integer check without tolerance
- `approxEqual()` - For comparing two floats with tolerance

### toInt()

```php
public static function toInt(float $f): int
```

Convert a float to an integer losslessly. Throws if the float cannot be converted without losing precision, rather than
returning a sentinel value - a caller that ignores the return value can't silently end up with the wrong int.

**Parameters:**

- `$f` (float) - The float to convert

**Returns:** `int` - The equivalent integer

**Throws:** `DomainException` - If the float cannot be converted to an int losslessly

**Behavior:**

- Returns the integer value if the float equals a whole number (e.g., 5.0 → 5, -10.0 → -10, 0.0 → 0).
- Throws if the float has a fractional part (e.g., 5.5, 0.1).
- Throws for non-finite values (NAN, ±INF).
- Handles negative zero (-0.0) by converting it to integer 0.
- Works for any float value (without fractional part) within PHP's integer range (PHP_INT_MIN to PHP_INT_MAX) - not
  limited to `MAX_SAFE_INT`.

**Examples:**

```php
// Successful conversion - whole number
Floats::toInt(5.0);  // 5

// Failed conversion - fractional part
Floats::toInt(5.5);  // throws DomainException

// Large whole numbers
Floats::toInt(1000000.0);  // 1000000

// Negative zero
Floats::toInt(-0.0);  // 0

// Powers of 2 work well (within precision)
Floats::toInt((float)(1 << 50));  // 1125899906842624 (2^50)

// PHP_INT_MIN is -2^63 (a power of 2), so it converts exactly
Floats::toInt((float)PHP_INT_MIN);  // PHP_INT_MIN

// PHP_INT_MAX is 2^63-1 (not a power of 2), loses precision as float
Floats::toInt((float)PHP_INT_MAX);  // throws DomainException

// Non-finite values
Floats::toInt(INF);  // throws DomainException
Floats::toInt(NAN);  // throws DomainException
```

**Use Cases:**

- Converting a float known (or expected) to represent a whole number, where a wrong silent result would be worse than an
  exception
- Validating that a float represents a whole number
- Conditional type conversion in generic numeric code, wrapped in a `try`/`catch` when the value might not convert

**Precision Limits:** On 64-bit systems, floats can exactly represent integers up to 2<sup>53</sup>
(9,007,199,254,740,992). Beyond this, not all integers can be represented exactly as floats. Powers of 2 can be
represented exactly up to much larger values.

**See Also:**

- `isSafeInt()` - Check convertibility without attempting the conversion
- `isInt()` - Check numeric integrality with no range restriction

---

## Random Methods

### rand()

```php
public static function rand(float $min = -PHP_FLOAT_MAX, float $max = PHP_FLOAT_MAX): float
```

Generate a random float in the specified range by constructing IEEE-754 components. This method can return any
representable float within the given range except -0.0, which is specifically excluded for two reasons:

- It's usually not a wanted value.
- To avoid a zero-equivalent value (-0.0 or +0.0) having twice the probability of being returned as any non-zero value.

**Parameters:**

- `$min` (float) - The minimum value (inclusive, default: -PHP_FLOAT_MAX)
- `$max` (float) - The maximum value (inclusive, default: PHP_FLOAT_MAX)

**Returns:** `float` - A random finite float in the range [min, max]

**Throws:**

- `RuntimeException` - If the system is not 64-bit
- `DomainException` - If min or max are non-finite (NAN, ±INF), or if min > max

**Examples:**

```php
// Random float across the entire finite float space
$f = Floats::rand();

// Random float in a specific range
$f = Floats::rand(0.0, 100.0);

// Random float between -1 and 1
$f = Floats::rand(-1.0, 1.0);

// When min equals max, returns that value
$f = Floats::rand(5.0, 5.0);  // 5.0
```

**Characteristics:**

- Can return **any representable float** in the given range.
- Will not return a special value (NAN, ±INF, or -0.0).
- Uses IEEE-754 component assembly (sign, exponent, fraction).
- Distribution is **not uniform** - there will be more values near zero due to IEEE-754 density.
- Handles ranges spanning zero correctly.

**How it works:**

1. Determines valid sign values based on min/max.
2. Determines valid exponent range based on min/max.
3. Generates random fraction bits.
4. Assembles components and validates result is in range and non-special.

**Use Cases:**

- Fuzzing and property-based testing with full float coverage
- Testing edge cases in floating-point algorithms
- Generating test data that exercises the full precision of floats

**See Also:**

- `randUniform()` - For uniformly distributed random floats

### randUniform()

```php
public static function randUniform(float $min, float $max): float
```

Generate a uniformly distributed random float in the specified range. The step size is automatically calculated using
ULP to ensure that the number of values the function can return for a given range is maximized, and the probability of
each possible return value is equal.

**Parameters:**

- `$min` (float) - The minimum value (inclusive)
- `$max` (float) - The maximum value (inclusive)

**Returns:** `float` - A random float in the range [min, max]

**Throws:**

- `DomainException` - If min or max are non-finite (NAN, ±INF), or if min > max
- `RandomException` - If an appropriate source of randomness is unavailable

**Examples:**

```php
// Random float between 0.0 and 1.0
$f = Floats::randUniform(0.0, 1.0);

// Random temperature between -10°C and 40°C
$temp = Floats::randUniform(-10.0, 40.0);

// When min equals max, returns that value
$f = Floats::randUniform(5.0, 5.0);  // 5.0
```

**Characteristics:**

- **Uniform distribution** in the numeric range.
- Uses `random_int()` internally for cryptographic randomness.
- Step size calculated using `ulp()` of the maximum magnitude.
- Avoids duplicate values by using ULP-based step calculation.
- Not all representable floats in the range are returnable, but distribution is uniform.
- Returns exactly min or max when randomly selected.

**How it works:**

1. Calculates ULP at the maximum magnitude.
2. Determines number of steps: `round($range / $ulp)`.
3. Generates random integer from 0 to number of steps.
4. Interpolates: `$min + ($randomStep / $numSteps) * $range`.

**Use Cases:**

- Monte Carlo simulations requiring uniform distribution
- Generating test data within specific ranges
- Random sampling for statistical analysis
- Cases where uniform distribution is more important than full float coverage

**Comparison with `rand()`:**

| Feature      | `rand()`                          | `randUniform()`             |
| ------------ | --------------------------------- | --------------------------- |
| Distribution | IEEE-754 density (more near zero) | Uniform                     |
| Precision    | Any representable float           | ULP-based steps             |
| Speed        | Slower (rejection loop)           | Faster (direct calculation) |
| Use case     | Fuzzing, edge cases               | Statistics, simulations     |
| Duplicates   | Possible                          | Avoided via ULP calculation |

**See Also:**

- `rand()` - For non-uniform distribution with full float coverage
- `ulp()` - Underlying calculation for step size

---

## Bit Operations

These methods work on the IEEE-754 bit representation of a double-width (64-bit) float. They can be used to extract
individual components, to construct a float from them, or find adjacent values in the float space.

### floatToBits()

```php
public static function floatToBits(float $f): int
```

Converts a float to its 64-bit integer representation. This reinterprets the IEEE-754 bit pattern as an integer.

**Parameters:**

- `$f` (float) - The float to convert

**Returns:** `int` - The 64-bit integer represented by the same bit pattern

**Throws:** `RuntimeException` - If the system is not 64-bit

**Examples:**

```php
// Get bit pattern of 1.0
$bits = Floats::floatToBits(1.0);
// $bits = 4607182418800017408 (0x3FF0000000000000)

// Positive and negative zero have different bit patterns
Floats::floatToBits(0.0);   // 0
Floats::floatToBits(-0.0);  // -9223372036854775808 (sign bit set)

// Round-trip with bitsToFloat()
$original = 3.14159;
$bits = Floats::floatToBits($original);
$restored = Floats::bitsToFloat($bits);
$original === $restored;  // true
```

**Use Cases:**

- Low-level float manipulation
- Extracting individual IEEE-754 components (sign, exponent, fraction)
- Implementing `next()` and `previous()` methods
- Understanding IEEE-754 representation
- Custom serialization of floats

**See Also:**

- `bitsToFloat()` - Convert bits back to float
- `disassemble()` - Extract individual IEEE-754 components

### bitsToFloat()

```php
public static function bitsToFloat(int $bits): float
```

Converts a 64-bit integer to a float by reinterpreting its bit pattern. This is different from casting an integer to a
float, which preserves the numeric value rather than the bit representation.

**Parameters:**

- `$bits` (int) - The 64-bit integer representing the desired bit pattern

**Returns:** `float` - The float with the specified bit pattern

**Throws:** `RuntimeException` - If the system is not 64-bit

**Note:** The IEEE 754 standard supports 2<sup>53</sup> - 2 distinct bit patterns that represent NAN values. While this
method can construct floats from any of these bit patterns, PHP normalizes all NAN values to a canonical representation
(0x7ff8000000000000) in subsequent operations.

**Examples:**

```php
// Construct 1.0 from its bit pattern (0x3FF0000000000000)
$f = Floats::bitsToFloat(4607182418800017408);  // 1.0

// Construct negative zero (0x8000000000000000 = PHP_INT_MIN as signed int)
$f = Floats::bitsToFloat(PHP_INT_MIN);  // -0.0

// Round-trip with floatToBits()
$original = -273.15;
$bits = Floats::floatToBits($original);
$restored = Floats::bitsToFloat($bits);
$original === $restored;  // true
```

**Note:** Hex literals with the high bit set (e.g. `0x8000000000000000`) overflow to `float` in PHP because integers are
signed 64-bit. Use the signed int equivalents or `PHP_INT_MIN` for the sign-bit-only pattern.

**Use Cases:**

- Constructing specific float values for testing
- Implementing `next()` and `previous()` methods
- Low-level IEEE-754 manipulation

**See Also:**

- `floatToBits()` - Convert float to bits
- `assemble()` - Construct float from IEEE-754 components

### disassemble()

```php
public static function disassemble(float $f): array
```

Disassemble a float into its IEEE-754 double-precision components.

**Parameters:**

- `$f` (float) - The float to disassemble

**Returns:** An associative array containing:
  - `bits` (int): Complete 64-bit representation
  - `sign` (int): 0 for positive, 1 for negative
  - `exponent` (int): 11-bit biased exponent (0-2047, bias is 1023)
  - `fraction` (int): 52-bit fraction/mantissa

**Throws:** `RuntimeException` - If the system is not 64-bit

**Examples:**

```php
// Disassemble 1.0
$parts = Floats::disassemble(1.0);
// $parts = ['bits' => ..., 'sign' => 0, 'exponent' => 1023, 'fraction' => 0]

// Disassemble -1.0
$parts = Floats::disassemble(-1.0);
// $parts = ['bits' => ..., 'sign' => 1, 'exponent' => 1023, 'fraction' => 0]

// Disassemble 1.5 (binary: 1.1)
$parts = Floats::disassemble(1.5);
// $parts = ['bits' => ..., 'sign' => 0, 'exponent' => 1023, 'fraction' => 2251799813685248] (2^51)

// Positive and negative zero have different representations
$pos = Floats::disassemble(0.0);   // sign = 0, exponent = 0, fraction = 0
$neg = Floats::disassemble(-0.0);  // sign = 1, exponent = 0, fraction = 0

// Infinity has exponent 2047 and fraction 0
$inf = Floats::disassemble(INF);   // sign = 0, exponent = 2047, fraction = 0

// NAN has exponent 2047 and non-zero fraction
$nan = Floats::disassemble(NAN);   // sign = ?, exponent = 2047, fraction > 0
```

**Use Cases:**

- Understanding IEEE-754 representation
- Debugging floating-point issues
- Implementing custom float manipulation algorithms
- Educational purposes

### assemble()

```php
public static function assemble(int $sign, int $exponent, int $fraction): float
```

Assemble a float from its IEEE-754 double-precision components.

**Parameters:**

- `$sign` (int) - The sign bit (0 = positive, 1 = negative)
- `$exponent` (int) - The 11-bit biased exponent (0-2047)
- `$fraction` (int) - The 52-bit fraction/mantissa (0 to 2^52 - 1)

**Returns:** `float` - The assembled float

**Throws:**

- `RuntimeException` - If the system is not 64-bit
- `DomainException` - If sign is not 0 or 1
- `DomainException` - If exponent is not in range [0, 2047]
- `DomainException` - If fraction is not in range [0, 2^52 - 1]

**Examples:**

```php
// Assemble 1.0
$f = Floats::assemble(0, 1023, 0);  // 1.0

// Assemble -1.0
$f = Floats::assemble(1, 1023, 0);  // -1.0

// Assemble 2.0 (exponent = 1024 = 1023 + 1)
$f = Floats::assemble(0, 1024, 0);  // 2.0

// Assemble 1.5
$f = Floats::assemble(0, 1023, 1 << 51);  // 1.5

// Assemble positive and negative zero
$posZero = Floats::assemble(0, 0, 0);  // 0.0
$negZero = Floats::assemble(1, 0, 0);  // -0.0

// Assemble infinity
$inf = Floats::assemble(0, 2047, 0);  // INF

// Assemble NAN (exponent 2047 with non-zero fraction)
$nan = Floats::assemble(0, 2047, 1);  // NAN
```

**Round-trip with disassemble():**

```php
$original = 42.5;
$parts = Floats::disassemble($original);
$reassembled = Floats::assemble($parts['sign'], $parts['exponent'], $parts['fraction']);
$original === $reassembled;  // true
```

**Use Cases:**

- Creating specific float bit patterns for testing
- Implementing custom random float generators
- Low-level float manipulation

### next()

```php
public static function next(float $f): float
```

Returns the next representable floating-point number after the given value. This performs bit-level manipulation to move
to the adjacent float in the IEEE-754 number line.

**Parameters:**

- `$f` (float) - The given number

**Returns:** `float` - The next floating-point number after the given number

**Throws:** `RuntimeException` - If the system is not 64-bit

**Behavior:**

- For positive numbers: returns the next larger float.
- For negative numbers: returns a float closer to zero.
- `-0.0` → `+0.0`
- `PHP_FLOAT_MAX` → `INF`
- `INF` → `INF`
- `-INF` → `-PHP_FLOAT_MAX`
- `NAN` → `NAN`

**Examples:**

```php
$f = 1.0;
$next = Floats::next($f);
// $next > $f (next representable float after 1.0)

// Navigate from negative zero to smallest positive number
$f = -0.0;
$next = Floats::next($f);  // 0.0
$next2 = Floats::next($next);  // smallest positive float

// At the boundary
$next = Floats::next(PHP_FLOAT_MAX);  // INF
```

**Use Cases:**

- Implementing "nextafter" functionality for numerical algorithms
- Testing floating-point edge cases
- Exploring the floating-point number space

### previous()

```php
public static function previous(float $f): float
```

Returns the previous representable floating-point number before the given value. This performs bit-level manipulation to
move to the adjacent float in the IEEE-754 number line.

**Parameters:**

- `$f` (float) - The given number

**Returns:** `float` - The previous floating-point number before the given number

**Throws:** `RuntimeException` - If the system is not 64-bit

**Behavior:**

- For positive numbers: returns a float closer to zero.
- For negative numbers: returns the next smaller (more negative) float.
- `+0.0` → `-0.0`
- `-PHP_FLOAT_MAX` → `-INF`
- `-INF` → `-INF`
- `INF` → `PHP_FLOAT_MAX`
- `NAN` → `NAN`

**Examples:**

```php
$f = 1.0;
$prev = Floats::previous($f);
// $prev < $f (previous representable float before 1.0)

// Navigate from positive zero to smallest negative number
$f = 0.0;
$prev = Floats::previous($f);  // -0.0
$prev2 = Floats::previous($prev);  // smallest negative float

// At the boundary
$prev = Floats::previous(-PHP_FLOAT_MAX);  // -INF
```

**Round-trip Property:**

For regular floats (not at boundaries):

```php
$f = 42.5;
Floats::next(Floats::previous($f)) === $f;  // true
Floats::previous(Floats::next($f)) === $f;  // true
```

**Use Cases:**

- Implementing interval arithmetic with tight bounds
- Generating test cases for numerical code
- Exploring floating-point precision limits

### ulp()

```php
public static function ulp(float $value): float
```

Calculate the Unit in Last Place (ULP) - the spacing between adjacent representable floats at a given magnitude. ULP
represents the gap between a float and the next largest representable float value.

**Parameters:**

- `$value` (float) - The value to calculate ULP for

**Returns:** `float` - The ULP spacing. Returns `NAN` for NAN, `INF` for ±INF.

**Throws:** `RuntimeException` - If the system is not 64-bit

**Behavior:**

- For finite values: returns `next(abs($value)) - abs($value)`.
- Larger magnitude numbers have larger ULP values.
- Uses absolute value, so ULP is the same for positive and negative values of the same magnitude.

**Examples:**

```php
// ULP of 1.0 is PHP_FLOAT_EPSILON (~2.22e-16)
Floats::ulp(1.0);  // 2.220446049250313e-16

// ULP scales with magnitude (larger values have larger gaps)
Floats::ulp(1000.0);  // ~1.14e-13
Floats::ulp(0.001);   // ~2.17e-19

// Large values have large ULP
Floats::ulp(1e20);  // ~16384.0

// Zero returns the smallest positive subnormal
Floats::ulp(0.0);   // ~4.94e-324
Floats::ulp(-0.0);  // Same as positive zero

// Negative values use absolute value
Floats::ulp(-100.0) === Floats::ulp(100.0);  // true

// Non-finite values
Floats::ulp(INF);   // INF
Floats::ulp(-INF);  // INF
Floats::ulp(NAN);   // NAN
```

**Relationship with next():**

The ULP is computed by taking the absolute value, then finding the gap to the next float:

```php
$value = -42.0;
$abs = abs($value);                // 42.0
$ulp = Floats::next($abs) - $abs;  // Gap from 42.0 to next float
Floats::ulp($value) === $ulp;      // true
```

**Understanding ULP:**

ULP reveals why floating-point precision decreases at larger magnitudes:

```php
// Around 1.0, ULP is ~2.22e-16
Floats::ulp(1.0);  // 2.220446049250313e-16

// Around 1 trillion, ULP is ~0.000122
Floats::ulp(1e12);  // 0.0001220703125

// This means there's no float between 1e12 and 1e12 + 0.000122
```

**Use Cases:**

- Understanding floating-point precision limits
- Implementing numerical algorithms with appropriate tolerances
- Calculating rounding error bounds in error analysis
- Testing floating-point code with appropriate epsilons
- Debugging precision issues in calculations
- Used by `randUniform()` to calculate optimal step size

**See Also:**

- `next()` - Get the next representable float
- `previous()` - Get the previous representable float
- `randUniform()` - Uses ULP for collision-free random generation
- [FloatWithError](https://github.com/mossy2100/PHP-Units/blob/main/docs/FloatWithError.md) - Uses ULP for the estimated
  increase in error resulting from arithmetic operations with floats

---

## See Also

- **[Integers](Integers.md)** - Integer utility methods
- **[Numbers](Numbers.md)** - General number-related utility methods
- **[Globals](Globals.md)** - Shared constants, including `M_TAU`
