<?php

declare(strict_types=1);

namespace OceanMoon\Core\Traits\Asserts;

use OceanMoon\Core\Floats;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Trait providing PHPUnit assertions for approximate floating-point equality.
 *
 * This trait adds an assertApproxEqual() method to PHPUnit test cases that provides
 * informative error messages when approximate equality assertions fail. Unlike using
 * assertTrue(Floats::approxEqual(...)), which only reports "Failed asserting that false is true",
 * this method shows the expected value, actual value, and the difference.
 *
 * This serves as a more flexible alternative to PHPUnit's assertEqualsWithDelta(), as it allows for both absolute and
 * relative tolerances. The error message is also somewhat more informative, showing the actual difference and the
 * tolerances used.
 *
 * Example usage:
 * ```php
 * class MyTest extends TestCase
 * {
 *     use FloatAssertions;
 *
 *     public function testCalculation(): void
 *     {
 *         $result = someCalculation();
 *         $this->assertApproxEqual(3.14159, $result);
 *     }
 * }
 * ```
 *
 * @see Floats::approxEqual() For the tolerance algorithm details.
 */
trait FloatAssertions
{
    /**
     * Assert that two floating-point values are approximately equal within specified tolerances.
     *
     * This method uses a combined absolute and relative tolerance approach, matching the algorithm
     * in Floats::approxEqual(). The absolute tolerance is checked first (useful for comparisons
     * near zero), and if that fails, the relative tolerance is checked (which scales with the
     * magnitude of the values).
     *
     * To compare using only absolute difference, set $relTol to 0.0.
     * To compare using only relative difference, set $absTol to 0.0.
     *
     * @param float $expected The expected value.
     * @param float $actual The actual value to compare.
     * @param float $relTol The maximum allowed relative difference.
     * @param float $absTol The maximum allowed absolute difference.
     * @param string $message Optional custom failure message.
     * @throws AssertionFailedError If $expected and $actual are not approximately equal within tolerance.
     */
    public function assertApproxEqual(
        float $expected,
        float $actual,
        float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
        float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE,
        string $message = ''
    ): void {
        if (Floats::approxEqual($expected, $actual, $relTol, $absTol)) {
            // Use a dummy assertion to count it.
            // @phpstan-ignore method.alreadyNarrowedType
            $this->assertTrue(true);
            return;
        }

        $diff = abs($expected - $actual);
        $maxMagnitude = max(abs($expected), abs($actual));
        $relDiff = $maxMagnitude !== 0.0 ? $diff / $maxMagnitude : INF;

        $defaultMessage = sprintf(
            "Failed asserting that %.15g approximately equals %.15g.\n" .
            "Absolute difference: %.15g (tolerance: %.15g)\n" .
            'Relative difference: %.15g (tolerance: %.15g)',
            $actual,
            $expected,
            $diff,
            $absTol,
            $relDiff,
            $relTol
        );

        $this->fail($message !== '' ? $message . "\n" . $defaultMessage : $defaultMessage);
    }
}
