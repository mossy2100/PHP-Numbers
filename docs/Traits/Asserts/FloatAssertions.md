# FloatAssertions

Trait providing PHPUnit assertions for approximate floating-point equality with informative error messages.

---

## Overview

The `FloatAssertions` trait adds custom assertions to PHPUnit test cases for comparing floating-point values with
configurable tolerances. Unlike using `assertTrue(Floats::approxEqual(...))`, which only reports "Failed asserting that
false is true", these assertions show the expected value, actual value, and the differences when they fail.

The trait provides:

- `assertApproxEqual()` - Assert two floats are approximately equal

---

## Methods

### assertApproxEqual()

```php
public function assertApproxEqual(
    float $expected,
    float $actual,
    float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
    float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE,
    string $message = ''
): void
```

Asserts that two floating-point values are approximately equal within specified tolerances.

**Parameters:**

- `$expected` (float) - The expected value
- `$actual` (float) - The actual value to compare
- `$relTol` (float) - Relative tolerance (default: 1e-9)
- `$absTol` (float) - Absolute tolerance (default: PHP_FLOAT_EPSILON ≈ 2.22e-16)
- `$message` (string) - Optional custom failure message prefix

**Throws:** `PHPUnit\Framework\AssertionFailedError` - If `$expected` and `$actual` are not approximately equal within
tolerance.

**Failure Message:**

When the assertion fails, it produces an informative message:

```
Failed asserting that 2.71828 approximately equals 3.14159.
Absolute difference: 0.42331 (tolerance: 2.22044604925031e-16)
Relative difference: 0.13474387173374 (tolerance: 1.0e-9)
```

---

## Examples

### Basic Usage

```php
use OceanMoon\Core\Traits\Asserts\FloatAssertions;
use PHPUnit\Framework\TestCase;

class CalculationTest extends TestCase
{
    use FloatAssertions;

    public function testCircleArea(): void
    {
        $radius = 5.0;
        $area = M_PI * $radius * $radius;

        $this->assertApproxEqual(78.5398, $area, absTol: 1e-4);
    }

    public function testSmallDifference(): void
    {
        $result = 1.0 - 0.9 - 0.1;

        // This would fail with assertSame() due to floating-point error
        $this->assertApproxEqual(0.0, $result);
    }
}
```

### With Custom Tolerances

```php
public function testScientificCalculation(): void
{
    $expected = 6.022e23;  // Avogadro's number
    $actual = calculateAvogadro();

    // Allow 0.1% relative error
    $this->assertApproxEqual($expected, $actual, relTol: 1e-3);
}

public function testNearZeroResult(): void
{
    $result = computeResidual();

    // Allow absolute error of 0.001
    $this->assertApproxEqual(0.0, $result, absTol: 1e-3);
}
```

### With Custom Error Message

```php
public function testPhysicsCalculation(): void
{
    $force = calculateForce($mass, $acceleration);

    $this->assertApproxEqual(
        expected: 9.80665,
        actual: $force,
        absTol: 1e-4,
        message: 'Force calculation for 1kg at Earth gravity'
    );
}
```

This produces on failure:

```
Force calculation for 1kg at Earth gravity
Failed asserting that 9.75 approximately equals 9.80665.
Absolute difference: 0.0566499999999994 (tolerance: 0.0001)
Relative difference: 0.0057766923465199 (tolerance: 1.0e-9)
```

---

## Comparison with assertTrue

**Before (uninformative):**

```php
$this->assertTrue(Floats::approxEqual(3.14159, $result));
// Failure: "Failed asserting that false is true."
```

**After (informative):**

```php
$this->assertApproxEqual(3.14159, $result);
// Failure: "Failed asserting that 2.71828 approximately equals 3.14159.
//          Absolute difference: 0.42331 (tolerance: 2.22044604925031e-16)
//          Relative difference: 0.13474387173374 (tolerance: 1.0e-9)"
```

---

## See Also

- [Floats.md](../../Floats.md) - The `Floats::approxEqual()` method used internally
- [ApproxEquatable.md](../Comparison/ApproxEquatable.md) - Trait for value objects needing approximate equality
