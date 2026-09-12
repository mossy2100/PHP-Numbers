# Equatable

Trait providing equality comparison functionality for objects.

---

## Overview

The `Equatable` trait provides a foundation for objects that support equality comparison. It defines an abstract
`equal()` method that must be implemented by classes using the trait.

It's kept separate from `Comparable` to follow the Interface Segregation Principle — some types can check equality
but have no natural ordering (e.g. `Complex` numbers can be equal but have no meaningful less-than/greater-than
relationship).

---

## Abstract Methods

### equal()

```php
abstract public function equal(mixed $other): bool
```

**You must implement this method.** Compare this object with another value and determine if they are equal.

**Parameters:**

- `$other` (mixed) - The value to compare with.

**Returns:** `bool` - `true` if the values are equal, `false` otherwise.

**Why `mixed` and not `self`:**

1. `self` is invariant across both trait composition and inheritance: if a class using this trait is subclassed and
   the subclass overrides `equal()`, `self` in the override would narrow to the subclass, which PHP rejects as an
   incompatible override of the trait method (bound to the base class).
2. Some types legitimately need to compare against a related-but-different type (e.g. `Complex` accepting `int` or
   `float`). There's no type hint for "self or number", so implementations must check the type of `$other`
   themselves.

**Implementation Guidelines:**

- Check the type of `$other` explicitly (typically `instanceof self`). Don't attempt to convert or coerce it.
- Throw (typically `InvalidArgumentException`) for any type that isn't a deliberate, documented exception to
  same-type-only comparison. Only widen to accept a related type where there's a genuine mathematical
  justification (e.g. `Complex` and `int`/`float`), and document it.
- Should be reflexive, symmetric, and transitive.
- For floating-point types, use `ApproxEquatable` alongside this trait.

**Throws:** Typically `InvalidArgumentException` for an incompatible type. See Implementation Guidelines above.

---

## Example

```php
use OceanMoon\Core\Traits\Comparison\Equatable;

class Point
{
    use Equatable;

    public function __construct(
        private float $x,
        private float $y
    ) {}

    public function equal(mixed $other): bool
    {
        if (!$other instanceof self) {
            throw new InvalidArgumentException('Cannot compare Point with ' . get_debug_type($other) . '.');
        }

        return $this->x === $other->x
            && $this->y === $other->y;
    }
}

$p1 = new Point(3.0, 4.0);
$p2 = new Point(3.0, 4.0);
$p3 = new Point(5.0, 6.0);

var_dump($p1->equal($p2)); // true
var_dump($p1->equal($p3)); // false
$p1->equal("string"); // throws InvalidArgumentException
```

---

## See Also

- [ComparisonTraits.md](ComparisonTraits.md) - Trait hierarchy overview
- [Comparable.md](Comparable.md) - Adds ordering operations
- [ApproxEquatable.md](ApproxEquatable.md) - Adds approximate equality
