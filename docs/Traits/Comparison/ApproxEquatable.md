# ApproxEquatable

Trait providing approximate equality comparison for objects with floating-point precision concerns.

---

## Overview

The `ApproxEquatable` trait extends `Equatable` by adding an `approxEqual()` method for tolerance-based comparison.
This is essential for types containing floating-point values where exact equality is unreliable due to precision
limitations.

| Name            | Description                                       | Implementation                  |
| --------------- | ------------------------------------------------- | ------------------------------- |
| `equal()`       | Exact equality comparison                         | You implement (via `Equatable`) |
| `approxEqual()` | Approximate equality with configurable tolerances | You implement                   |

---

## Abstract Methods

### approxEqual()

```php
abstract public function approxEqual(
    mixed $other,
    float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
    float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
): bool
```

**You must implement this method.** It should compare this object with another using tolerance-based comparison
suitable for floating-point values.

**Parameters:**

- `$other` (mixed) - The value to compare with.
- `$relTol` (float) - Relative tolerance (default: 1e-9).
- `$absTol` (float) - Absolute tolerance (default: PHP_FLOAT_EPSILON ≈ 2.22e-16).

**Returns:** `bool` - `true` if approximately equal within tolerances, `false` otherwise.

**Implementation Guidelines:**

- Check the type of `$other` explicitly (typically `instanceof self`). Don't attempt to convert or coerce it. This matches `equal()`'s contract (see [Equatable.md](Equatable.md)).
- Throw (typically `InvalidArgumentException`) for any type that isn't a deliberate, documented exception to
  same-type-only comparison.
- Use `Floats::approxEqual()` for the actual float comparisons: it checks absolute tolerance first (`|a - b| ≤
  absTol`, useful near zero), then relative tolerance (`|a - b| ≤ relTol * max(|a|, |b|)`, which scales with
  magnitude).
- For composite types, check each component separately with the same tolerances.
- Passing `$relTol = 0.0, $absTol = 0.0` is equivalent to exact equality. Callers who want that should use
  `equal()` instead.

---

## Example

```php
use OceanMoon\Core\Floats;
use OceanMoon\Core\Traits\Comparison\ApproxEquatable;

class Complex
{
    use ApproxEquatable;

    public function __construct(
        private float $real,
        private float $imaginary
    ) {}

    public function equal(mixed $other): bool
    {
        if (!$other instanceof self) {
            throw new InvalidArgumentException('Cannot compare Complex with ' . get_debug_type($other) . '.');
        }

        return $this->real === $other->real
            && $this->imaginary === $other->imaginary;
    }

    public function approxEqual(
        mixed $other,
        float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
        float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
    ): bool {
        if (!$other instanceof self) {
            throw new InvalidArgumentException('Cannot compare Complex with ' . get_debug_type($other) . '.');
        }

        // Both components must be within tolerance
        return Floats::approxEqual($this->real, $other->real, $relTol, $absTol)
            && Floats::approxEqual($this->imaginary, $other->imaginary, $relTol, $absTol);
    }
}

$z1 = new Complex(3.0, 4.0);
$z2 = new Complex(3.0, 4.0);
$z3 = new Complex(3.00000001, 4.00000001);

var_dump($z1->equal($z2));        // true (exact match)
var_dump($z1->equal($z3));        // false (not exact)
var_dump($z1->approxEqual($z3));  // true (within default tolerance)
$z1->equal("not a complex");      // throws InvalidArgumentException
```

---

## See Also

- [ComparisonTraits.md](ComparisonTraits.md) - Trait hierarchy overview
- [Equatable.md](Equatable.md) - Base equality trait this includes
- [ApproxComparable.md](ApproxComparable.md) - For types with both ordering and approximate equality
- [Floats.md](../../Floats.md) - The `Floats::approxEqual()` tolerance algorithm
