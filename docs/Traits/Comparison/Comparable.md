# Comparable

Trait providing comparison operations for objects with natural ordering.

---

## Overview

The `Comparable` trait provides a complete set of comparison methods based on a single `compare()` method that you
implement. It uses the `Equatable` trait and adds ordering methods.

The trait follows the **Template Method Pattern**: you implement the `compare()` method, and all other methods are
automatically provided.

| Name                   | Description                       | Implementation                      |
| ---------------------- | --------------------------------- | ----------------------------------- |
| `compare()`            | Ordering comparison               | You implement                       |
| `equal()`              | Check equality                    | Provided (delegates to `compare()`) |
| `lessThan()`           | Check if less than                | Provided                            |
| `lessThanOrEqual()`    | Check if less than or equal to    | Provided                            |
| `greaterThan()`        | Check if greater than             | Provided                            |
| `greaterThanOrEqual()` | Check if greater than or equal to | Provided                            |

---

## Abstract Methods

### compare()

```php
abstract public function compare(mixed $other): int
```

**You must implement this method.** It should compare this object with another and return:

- `-1` if this object is less than `$other`
- `0` if this object equals `$other`
- `1` if this object is greater than `$other`

**Parameters:**

- `$other` (mixed) - The value to compare with.

**Returns:** `int` - Exactly `-1`, `0`, or `1`.

**Implementation Guidelines:**

- Must return **exactly** -1, 0, or 1 (not just negative/zero/positive). The convenience methods use strict
  equality checks. Use `Numbers::sign()` to normalize the spaceship operator's result.
- Check the type of `$other` explicitly (typically `instanceof self`). Don't attempt to convert or coerce it.
- Throw (typically `InvalidArgumentException`) for any type that isn't a deliberate, documented exception to
  same-type-only comparison. See [Equatable.md](Equatable.md) for the reasoning.

---

## Concrete Methods

### equal()

```php
public function equal(mixed $other): bool
```

Check if this object equals another. Provided by the trait, delegating to `compare()`. Returns `true` only if
`compare()` returns `0`. Propagates whatever `compare()` throws for incompatible types.

### lessThan(), lessThanOrEqual(), greaterThan(), greaterThanOrEqual()

```php
public function lessThan(mixed $other): bool
public function lessThanOrEqual(mixed $other): bool
public function greaterThan(mixed $other): bool
public function greaterThanOrEqual(mixed $other): bool
```

All provided by the trait, built on `compare()`. Each propagates whatever `compare()` throws for incompatible types.

---

## Example

```php
use OceanMoon\Core\Numbers;
use OceanMoon\Core\Traits\Comparison\Comparable;

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

$v1 = new Version(1, 2, 3);
$v2 = new Version(1, 2, 4);
$v3 = new Version(2, 0, 0);

var_dump($v1->lessThan($v2));     // true (1.2.3 < 1.2.4)
var_dump($v1->lessThan($v3));     // true (1.2.3 < 2.0.0)
var_dump($v3->greaterThan($v1));  // true (2.0.0 > 1.2.3)
```

---

## See Also

- [ComparisonTraits.md](ComparisonTraits.md) - Trait hierarchy overview
- [Equatable.md](Equatable.md) - Base equality trait this includes
- [ApproxComparable.md](ApproxComparable.md) - For approximate comparison with ordering
