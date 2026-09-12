# Comparison Traits

A hierarchical set of traits providing equality and ordering comparison operations for custom types.

---

## Trait Hierarchy

```
        Equatable
        ↙     ↘
Comparable   ApproxEquatable
        ↘     ↙
    ApproxComparable
```

**Key:**

- `→` indicates trait composition (`use`)
- `ApproxComparable` uses both `Comparable` and `ApproxEquatable`

---

## Quick Reference

| Trait                                     | Uses                             | Must Implement               | Provides                                       | Use When                                       |
| ----------------------------------------- | -------------------------------- | ---------------------------- | ---------------------------------------------- | ---------------------------------------------- |
| [`Equatable`](Equatable.md)               | -                                | `equal()`                    | -                                              | Need exact equality only                       |
| [`Comparable`](Comparable.md)             | `Equatable`                      | `compare()`                  | `equal()`, `lessThan()`, `greaterThan()`, etc. | Need exact equality + ordering                 |
| [`ApproxEquatable`](ApproxEquatable.md)   | `Equatable`                      | `equal()`, `approxEqual()`   | -                                              | Need exact + approximate equality, no ordering |
| [`ApproxComparable`](ApproxComparable.md) | `Comparable` + `ApproxEquatable` | `compare()`, `approxEqual()` | All comparison methods + `approxCompare()`     | Need full comparison suite                     |

---

## Type Handling

Every method in every trait here is typed `mixed $other`, never `self`. Two reasons:

1. `self` is invariant across trait composition and inheritance: if a class using one of these traits is subclassed and
   the subclass overrides a method like `equal()` or `compare()`, `self` in that override would narrow to the subclass.
   PHP rejects this as an incompatible override of the trait method (bound to the base class).
2. A handful of types legitimately compare against a related-but-different type (e.g. `Complex` accepting `int` or
   `float`). There's no type hint for "self or number", so implementations check the type of `$other` themselves.

Because the type hint can't do the work, implementations are expected to check `$other`'s type explicitly (typically
`instanceof self`) and **throw** (typically `InvalidArgumentException`) for anything that isn't a deliberate, documented
exception. Never silently attempt a conversion. This mirrors why `==`/`!=` are avoided in favor of `===`/`!==` in
modern PHP: implicit type juggling in comparisons is a recurring source of bugs. Widening a comparison method to accept
a related type should be rare, mathematically justified on a case-by-case basis, and documented at the point of
implementation, not as a general-purpose "convert whatever you're given" policy.

---

## How They Work Together

### Equatable (Base Trait)

```php
trait Equatable
{
    abstract public function equal(mixed $other): bool;
}
```

**You implement:** `equal()` for exact equality comparison

### Comparable (Uses Equatable)

```php
trait Comparable
{
    use Equatable;

    abstract public function compare(mixed $other): int;
    // Provides: equal(), lessThan(), greaterThan(), etc.
}
```

**You implement:** `compare()` returning -1, 0, or 1

**You get:** `equal()` (based on `compare()`), `lessThan()`, `greaterThan()`, `lessThanOrEqual()`,
`greaterThanOrEqual()`

**Note:** You don't need to implement `equal()`, as the trait provides it.

### ApproxEquatable (Uses Equatable)

```php
trait ApproxEquatable
{
    use Equatable;

    abstract public function approxEqual(
        mixed $other,
        float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
        float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
    ): bool;
}
```

**You implement:** `equal()` and `approxEqual()`

### ApproxComparable (Uses Both)

```php
trait ApproxComparable
{
    use Comparable;
    use ApproxEquatable;

    // Provides: approxCompare()
}
```

**You implement:** `compare()` and `approxEqual()`

**You get:** All methods from both `Comparable` and `ApproxEquatable`, plus `approxCompare()`

**Note:** You don't implement `equal()`, as `Comparable` provides it.

---

## Usage Examples

### Equatable Only (No Ordering)

```php
class Color
{
    use Equatable;

    public function __construct(private int $rgb) {}

    public function equal(mixed $other): bool
    {
        if (!$other instanceof self) {
            throw new InvalidArgumentException('Cannot compare Color with ' . get_debug_type($other) . '.');
        }
        return $this->rgb === $other->rgb;
    }
}
```

### Comparable (Exact Ordering)

```php
class Version
{
    use Comparable;

    public function __construct(
        private int $major,
        private int $minor,
        private int $patch
    ) {}

    public function compare(mixed $other): int
    {
        if (!$other instanceof self) {
            throw new InvalidArgumentException('Cannot compare Version with ' . get_debug_type($other) . '.');
        }

        $result = $this->major <=> $other->major
            ?: $this->minor <=> $other->minor
            ?: $this->patch <=> $other->patch;

        return Numbers::sign($result);
    }

    // equal(), lessThan(), etc. automatically provided
}
```

### ApproxEquatable (Approximate Equality, No Ordering)

```php
class Complex
{
    use ApproxEquatable;

    public function __construct(private float $real, private float $imag) {}

    public function equal(mixed $other): bool
    {
        if (!$other instanceof self) {
            throw new InvalidArgumentException('Cannot compare Complex with ' . get_debug_type($other) . '.');
        }
        return $this->real === $other->real
            && $this->imag === $other->imag;
    }

    public function approxEqual(
        mixed $other,
        float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
        float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
    ): bool {
        if (!$other instanceof self) {
            throw new InvalidArgumentException('Cannot compare Complex with ' . get_debug_type($other) . '.');
        }

        return Floats::approxEqual($this->real, $other->real, $relTol, $absTol)
            && Floats::approxEqual($this->imag, $other->imag, $relTol, $absTol);
    }
}
```

### ApproxComparable (Full Suite)

```php
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

## Method Override Rules

You can override any provided method if needed, but this is rarely necessary. Don't override provided methods
(`equal()`, `lessThan()`, `approxCompare()`, etc.) unless you have a specific reason. The default implementations are
well-tested and consistent.

---

## Choosing the Right Trait

**Use `Equatable` when:**

- You only need equality comparison
- Your type has no natural ordering (e.g., colors, sets)

**Use `Comparable` when:**

- Your type has a natural ordering
- You need exact comparison
- Integer or rational number types

**Use `ApproxEquatable` when:**

- You need both exact and approximate equality
- Your type contains floating-point values
- Your type has no natural ordering (e.g., complex numbers, matrices)

**Use `ApproxComparable` when:**

- Your type has a natural ordering
- You need both exact and approximate comparison
- Mixed integer/float types (e.g., rational numbers converted to float)

---

## See Also

- [Equatable.md](Equatable.md) - Base trait for equality
- [Comparable.md](Comparable.md) - Trait for ordering
- [ApproxEquatable.md](ApproxEquatable.md) - Trait for approximate equality
- [ApproxComparable.md](ApproxComparable.md) - Trait for complete comparison
