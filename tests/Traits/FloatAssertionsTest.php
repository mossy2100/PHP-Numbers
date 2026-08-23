<?php

declare(strict_types=1);

namespace OceanMoon\Core\Tests\Traits;

use OceanMoon\Core\Traits\Asserts\FloatAssertions;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the FloatAssertions trait.
 */
#[CoversTrait(FloatAssertions::class)]
final class FloatAssertionsTest extends TestCase
{
    use FloatAssertions;

    #region Method assertApproxEqual() tests.

    /**
     * Test assertApproxEqual passes for equal values.
     */
    public function testAssertApproxEqualPassesForEqualValues(): void
    {
        $this->assertApproxEqual(1.0, 1.0);
        $this->assertApproxEqual(0.0, 0.0);
        $this->assertApproxEqual(-42.5, -42.5);
    }

    /**
     * Test assertApproxEqual passes for values within absolute tolerance.
     */
    public function testAssertApproxEqualPassesWithinAbsoluteTolerance(): void
    {
        // Default absolute tolerance is PHP_FLOAT_EPSILON (~2.2e-16)
        $this->assertApproxEqual(0.0, PHP_FLOAT_EPSILON / 2);
        // With custom larger tolerance
        $this->assertApproxEqual(0.0, 1e-10, absTol: 1e-9);
    }

    /**
     * Test assertApproxEqual passes for values within relative tolerance.
     */
    public function testAssertApproxEqualPassesWithinRelativeTolerance(): void
    {
        $large = 1e15;
        $this->assertApproxEqual($large, $large + 1e5);
    }

    /**
     * Test assertApproxEqual passes with custom tolerances.
     */
    public function testAssertApproxEqualPassesWithCustomTolerances(): void
    {
        $this->assertApproxEqual(100.0, 105.0, 0.1, 1.0);
        $this->assertApproxEqual(0.0, 0.5, 0.0, 1.0);
    }

    /**
     * Test assertApproxEqual fails for values outside tolerance.
     */
    public function testAssertApproxEqualFailsOutsideTolerance(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('approximately equals');

        $this->assertApproxEqual(1.0, 2.0);
    }

    /**
     * Test assertApproxEqual failure message contains expected and actual values.
     */
    public function testAssertApproxEqualFailureMessageContainsValues(): void
    {
        try {
            $this->assertApproxEqual(3.14159, 2.71828);
            $this->fail('Expected AssertionFailedError was not thrown');
        } catch (AssertionFailedError $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('2.71828', $message);
            $this->assertStringContainsString('3.14159', $message);
            $this->assertStringContainsString('Absolute difference', $message);
            $this->assertStringContainsString('Relative difference', $message);
        }
    }

    /**
     * Test assertApproxEqual failure message reports the correct relative difference value.
     *
     * The relative difference is diff / max(|expected|, |actual|), matching Floats::approxEqual()'s own
     * algorithm - not diff / expected, which gives a misleading value when the two magnitudes differ a lot.
     */
    public function testAssertApproxEqualFailureMessageReportsCorrectRelativeDifference(): void
    {
        try {
            $this->assertApproxEqual(1.0, 1000000.0);
            $this->fail('Expected AssertionFailedError was not thrown');
        } catch (AssertionFailedError $e) {
            $this->assertStringContainsString('Relative difference: 0.999999 ', $e->getMessage());
        }
    }

    /**
     * Test assertApproxEqual failure message includes custom message.
     */
    public function testAssertApproxEqualFailureMessageIncludesCustomMessage(): void
    {
        try {
            $this->assertApproxEqual(1.0, 2.0, message: 'Custom error context');
            $this->fail('Expected AssertionFailedError was not thrown');
        } catch (AssertionFailedError $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('Custom error context', $message);
        }
    }

    /**
     * Test assertApproxEqual handles infinity.
     */
    public function testAssertApproxEqualHandlesInfinity(): void
    {
        $this->assertApproxEqual(INF, INF);
        $this->assertApproxEqual(-INF, -INF);
    }

    /**
     * Test assertApproxEqual fails for mismatched infinity.
     */
    public function testAssertApproxEqualFailsForMismatchedInfinity(): void
    {
        $this->expectException(AssertionFailedError::class);

        $this->assertApproxEqual(INF, -INF);
    }

    #endregion
}
