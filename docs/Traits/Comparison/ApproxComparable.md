# ApproxComparable

Trait providing complete comparison operations with both exact and approximate equality for objects with natural
ordering and floating-point precision concerns.

---

## Overview

The `ApproxComparable` trait combines `Comparable` and `ApproxEquatable` to provide a complete set of comparison
operations including approximate equality. This is ideal for types with natural ordering that contain
floating-point values (e.g., Rational numbers).

| Name                   | Description                                    | Implementation              |
| ---------------------- | ---------------------------------------------- | --------------------------- |
| `compare()`            | Exact ordering comparison                      | You implement               |
| `approxEqual()`        | Approximate equality                           | You implement               |
| `approxCompare()`      | Approximate ordering comparison with tolerance | Provided                    |
| `equal()`              | Exact equality                                 | Provided (via `Comparable`) |
| `lessThan()`           | Check if less than                             | Provided (via `Comparable`) |
| `lessThanOrEqual()`    | Check if less than or equal to                 | Provided (via `Comparable`) |
| `greaterThan()`        | Check if greater than                          | Provided (via `Comparable`) |
| `greaterThanOrEqual()` | Check if greater than or equal to              | Provided (via `Comparable`) |

---

## Abstract Methods

### compare()

```php
abstract public function compare(mixed $other): int
```

**You must implement this method.** See [Comparable.md](Comparable.md) for full documentation.

### approxEqual()

```php
abstract public function approxEqual(
    mixed $other,
    float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
    float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
): bool
```

**You must implement this method.** See [ApproxEquatable.md](ApproxEquatable.md) for full documentation.

Both abstract methods should check the type of `$other` explicitly (typically `instanceof self`) and throw
(typically `InvalidArgumentException`) for anything that isn't a deliberate, documented exception to
same-type-only comparison. See [Equatable.md](Equatable.md) for the reasoning.

---

## Concrete Methods

### approxCompare()

```php
public function approxCompare(
    mixed $other,
    float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
    float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
): int
```

Compare with approximate equality awareness. Returns 0 if values are approximately equal within tolerances,
otherwise performs exact comparison. Provided by the trait, built on `approxEqual()` and `compare()`.

**Use Cases:**

- Sorting with approximate equality "buckets"
- Finding approximate min/max
- Range queries with tolerance

---

## Example

```php
use OceanMoon\Core\Floats;
use OceanMoon\Core\Numbers;
use OceanMoon\Core\Traits\Comparison\ApproxComparable;

class Rational
{
    use ApproxComparable;

    public function __construct(private int $num, private int $den) {}

    public function compare(mixed $other): int
    {
        if (!$other instanceof self) {
            throw new InvalidArgumentException('Cannot compare Rational with ' . get_debug_type($other) . '.');
        }

        $left = $this->num * $other->den;
        $right = $other->num * $this->den;

        return Numbers::sign($left <=> $right);
    }

    public function approxEqual(
        mixed $other,
        float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
        float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
    ): bool {
        if (!$other instanceof self) {
            throw new InvalidArgumentException('Cannot compare Rational with ' . get_debug_type($other) . '.');
        }

        return Floats::approxEqual(
            $this->num / $this->den,
            $other->num / $other->den,
            $relTol,
            $absTol
        );
    }

    // equal(), lessThan(), greaterThan(), approxCompare(), etc. all provided
}
```

---

## See Also

- [ComparisonTraits.md](ComparisonTraits.md) - Trait hierarchy overview, including guidance on choosing between
  `equal()`/`approxEqual()`/`compare()`/`approxCompare()`
- [Comparable.md](Comparable.md) - Base ordering trait this includes
- [ApproxEquatable.md](ApproxEquatable.md) - Base approximate-equality trait this includes
