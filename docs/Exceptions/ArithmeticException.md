# ArithmeticException

Exception thrown when an arithmetic operation has no defined result for the given operands.

---

## Overview

`ArithmeticException` covers cases like division by zero, logarithm of a non-positive number or with base 0 or 1, and
other operations that are undefined for specific inputs.

For a plain `float`, PHP signals these cases by returning `NAN` or `±INF`. Value types with no such sentinel to fall
back on, such as `Complex`, `Rational`, `Vector`, `Matrix`, and similar exact/structured numeric types, throw this exception instead.

The exception extends `DomainException`, so it's still caught by any existing `catch (DomainException)` code, while
allowing more specific handling of undefined-operation failures.

`ArithmeticException` displaces PHP's built-in `DivisionByZeroError`: "Error" types are reserved for engine-emitted
conditions and should in principle never be thrown from userland code (see the project's
[Exception Conventions](../../../../docs/guidelines/EXCEPTIONS.md)). It should also not be confused with PHP's built-in
`ArithmeticError`, which signals engine-level conditions (e.g. arithmetic overflow), not a recoverable domain-level
operation failure.

---

## Class Definition

```php
namespace OceanMoon\Core\Exceptions;

class ArithmeticException extends DomainException
```

---

## Usage Example

```php
use OceanMoon\Core\Exceptions\ArithmeticException;

class Fraction
{
    public function __construct(private int $num, private int $den) {}

    public function inv(): self
    {
        if ($this->num === 0) {
            throw new ArithmeticException('Cannot take reciprocal of zero.');
        }
        return new self($this->den, $this->num);
    }
}

try {
    (new Fraction(0, 1))->inv();
} catch (ArithmeticException $e) {
    echo $e->getMessage();
    // Output: Cannot take reciprocal of zero.
}
```

---

## When to Use

Throw this exception when:

- Dividing by zero (or an operation that reduces to it, e.g. taking the reciprocal of zero).
- Computing a logarithm of a non-positive number, or with a base of 0 or 1.
- Inverting a singular matrix (zero determinant).
- Any other operation that is mathematically undefined for the given operands, where the type has no `NAN`/`INF`-style
  sentinel to return instead.

---

## When Not to Use

Use other exceptions when:

- The argument is the wrong type entirely (use `InvalidArgumentException`).
- The value is out of the valid domain but the operation itself is well-defined for other operands of the same shape
  (e.g. negative dimensions, wrong shape for construction; in these cases, use `DomainException` directly).
- A native PHP division (`/`) or `intdiv()` on already-validated operands would throw `DivisionByZeroError`. Guard
  against zero explicitly and throw `ArithmeticException` instead, rather than letting the native error propagate.

---

## See Also

- **[DomainException](https://www.php.net/manual/en/class.domainexception.php)** - Parent class
- **[Exception Conventions](../../../../docs/guidelines/EXCEPTIONS.md)** - Project-wide exception usage guidelines
- `OceanMoon\Math\Complex`, `OceanMoon\Math\Rational`, `OceanMoon\Math\Vector`, `OceanMoon\Math\Matrix` - all use
  `ArithmeticException` for undefined arithmetic operations
