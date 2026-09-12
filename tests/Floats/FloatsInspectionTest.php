<?php

declare(strict_types=1);

namespace OceanMoon\Core\Tests\Floats;

use DomainException;
use OceanMoon\Core\Floats;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Floats utility class - inspection methods.
 */
#[CoversClass(Floats::class)]
final class FloatsInspectionTest extends TestCase
{
    #region Method isNegativeZero() tests.

    /**
     * Test detection of negative zero.
     */
    public function testIsNegativeZero(): void
    {
        // Test that -0.0 is correctly identified as negative zero.
        $this->assertTrue(Floats::isNegativeZero(-0.0));

        // Test that positive zero is not negative zero.
        $this->assertFalse(Floats::isNegativeZero(0.0));

        // Test that positive values are not negative zero.
        $this->assertFalse(Floats::isNegativeZero(1.0));

        // Test that negative values are not negative zero.
        $this->assertFalse(Floats::isNegativeZero(-1.0));

        // Test that infinity values are not negative zero.
        $this->assertFalse(Floats::isNegativeZero(INF));
        $this->assertFalse(Floats::isNegativeZero(-INF));

        // Test that NAN is not negative zero.
        $this->assertFalse(Floats::isNegativeZero(NAN));
    }

    #endregion

    #region Method isPositiveZero() tests.

    /**
     * Test detection of positive zero.
     */
    public function testIsPositiveZero(): void
    {
        // Test that +0.0 is correctly identified as positive zero.
        $this->assertTrue(Floats::isPositiveZero(0.0));

        // Test that negative zero is not positive zero.
        $this->assertFalse(Floats::isPositiveZero(-0.0));

        // Test that positive values are not positive zero.
        $this->assertFalse(Floats::isPositiveZero(1.0));

        // Test that negative values are not positive zero.
        $this->assertFalse(Floats::isPositiveZero(-1.0));

        // Test that infinity values are not positive zero.
        $this->assertFalse(Floats::isPositiveZero(INF));
        $this->assertFalse(Floats::isPositiveZero(-INF));

        // Test that NAN is not positive zero.
        $this->assertFalse(Floats::isPositiveZero(NAN));
    }

    #endregion

    #region Method isNegative() tests.

    /**
     * Test detection of negative values.
     */
    public function testIsNegative(): void
    {
        // Test that negative values are correctly identified.
        $this->assertTrue(Floats::isNegative(-1.0));
        $this->assertTrue(Floats::isNegative(-0.5));
        $this->assertTrue(Floats::isNegative(-100.0));

        // Test that negative zero is identified as negative.
        $this->assertTrue(Floats::isNegative(-0.0));

        // Test that negative infinity is identified as negative.
        $this->assertTrue(Floats::isNegative(-INF));

        // Test that positive values are not negative.
        $this->assertFalse(Floats::isNegative(1.0));
        $this->assertFalse(Floats::isNegative(0.5));

        // Test that positive zero is not negative.
        $this->assertFalse(Floats::isNegative(0.0));

        // Test that positive infinity is not negative.
        $this->assertFalse(Floats::isNegative(INF));

        // Test that NAN is not negative.
        $this->assertFalse(Floats::isNegative(NAN));
    }

    #endregion

    #region Method isPositive() tests.

    /**
     * Test detection of positive values.
     */
    public function testIsPositive(): void
    {
        // Test that positive values are correctly identified.
        $this->assertTrue(Floats::isPositive(1.0));
        $this->assertTrue(Floats::isPositive(0.5));
        $this->assertTrue(Floats::isPositive(100.0));

        // Test that positive zero is identified as positive.
        $this->assertTrue(Floats::isPositive(0.0));

        // Test that positive infinity is identified as positive.
        $this->assertTrue(Floats::isPositive(INF));

        // Test that negative values are not positive.
        $this->assertFalse(Floats::isPositive(-1.0));
        $this->assertFalse(Floats::isPositive(-0.5));

        // Test that negative zero is not positive.
        $this->assertFalse(Floats::isPositive(-0.0));

        // Test that negative infinity is not positive.
        $this->assertFalse(Floats::isPositive(-INF));

        // Test that NAN is not positive.
        $this->assertFalse(Floats::isPositive(NAN));
    }

    #endregion

    #region Method isSpecial() tests.

    /**
     * Test detection of special float values.
     */
    public function testIsSpecial(): void
    {
        // Test that NAN is identified as special.
        $this->assertTrue(Floats::isSpecial(NAN));

        // Test that negative zero is identified as special.
        $this->assertTrue(Floats::isSpecial(-0.0));

        // Test that positive infinity is identified as special.
        $this->assertTrue(Floats::isSpecial(INF));

        // Test that negative infinity is identified as special.
        $this->assertTrue(Floats::isSpecial(-INF));

        // Test that positive zero is not special.
        $this->assertFalse(Floats::isSpecial(0.0));

        // Test that regular positive values are not special.
        $this->assertFalse(Floats::isSpecial(1.0));
        $this->assertFalse(Floats::isSpecial(42.5));

        // Test that regular negative values are not special.
        $this->assertFalse(Floats::isSpecial(-1.0));
        $this->assertFalse(Floats::isSpecial(-42.5));
    }

    #endregion

    #region Method getExponent() tests.

    /**
     * Test getExponent() with typical positive values at various magnitudes.
     */
    public function testGetExponentWithPositiveValues(): void
    {
        $this->assertSame(0, Floats::getExponent(5.0));
        $this->assertSame(1, Floats::getExponent(50.0));
        $this->assertSame(2, Floats::getExponent(500.0));
        $this->assertSame(-1, Floats::getExponent(0.5));
        $this->assertSame(-2, Floats::getExponent(0.05));
    }

    /**
     * Test getExponent() with negative values returns the same exponent as the equivalent positive value.
     */
    public function testGetExponentWithNegativeValues(): void
    {
        $this->assertSame(3, Floats::getExponent(-1234.0));
        $this->assertSame(3, Floats::getExponent(1234.0));
    }

    /**
     * Test getExponent() with 0.0 returns 0 rather than -INF.
     */
    public function testGetExponentWithZero(): void
    {
        $this->assertSame(0, Floats::getExponent(0.0));
    }

    /**
     * Test getExponent() with exact powers of 10, guarding against log10() rounding error (e.g. some platforms
     * return log10(1000) as 2.9999999999999996 rather than exactly 3.0).
     */
    public function testGetExponentWithExactPowersOfTen(): void
    {
        $this->assertSame(3, Floats::getExponent(1000.0));
        $this->assertSame(-3, Floats::getExponent(0.001));
        $this->assertSame(21, Floats::getExponent(1.0e21));
        $this->assertSame(-7, Floats::getExponent(1.0e-7));
    }

    /**
     * Test getExponent() with values just below and above a power-of-ten boundary.
     */
    public function testGetExponentNearBoundary(): void
    {
        $this->assertSame(0, Floats::getExponent(9.999999));
        $this->assertSame(1, Floats::getExponent(10.00001));
    }

    /**
     * Test getExponent() with a value one ULP below an exact power of ten, where log10() actually overestimates
     * the exponent on this platform (e.g. log10() of the double just below 1.0e15 returns exactly 15.0 rather
     * than a value just under it), exercising the mantissa < 1.0 correction.
     */
    public function testGetExponentWithLog10OverestimateJustBelowPowerOfTen(): void
    {
        $this->assertSame(14, Floats::getExponent(999999999999999.9));
    }

    /**
     * Test getExponent() throws for NAN.
     */
    public function testGetExponentWithNanThrows(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid value: NAN. Must be finite.');
        Floats::getExponent(NAN);
    }

    /**
     * Test getExponent() throws for +INF and -INF.
     */
    public function testGetExponentWithInfThrows(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid value: INF. Must be finite.');
        Floats::getExponent(INF);
    }

    /**
     * Test getExponent() throws for -INF.
     */
    public function testGetExponentWithNegativeInfThrows(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid value: -INF. Must be finite.');
        Floats::getExponent(-INF);
    }

    #endregion
}
