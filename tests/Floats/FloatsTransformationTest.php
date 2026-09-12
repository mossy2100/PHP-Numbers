<?php

declare(strict_types=1);

namespace OceanMoon\Core\Tests\Floats;

use DomainException;
use OceanMoon\Core\Floats;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use const OceanMoon\Core\M_TAU;

/**
 * Test class for Floats utility class - transformation methods.
 */
#[CoversClass(Floats::class)]
final class FloatsTransformationTest extends TestCase
{
    #region Method normalizeZero() tests.

    /**
     * Test normalization of zero values.
     */
    public function testNormalizeZero(): void
    {
        // Test that negative zero is normalized to positive zero.
        $this->assertSame(0.0, Floats::normalizeZero(-0.0));

        // Test that positive zero remains positive zero.
        $this->assertSame(0.0, Floats::normalizeZero(0.0));

        // Test that positive values are unchanged.
        $this->assertSame(1.5, Floats::normalizeZero(1.5));

        // Test that negative values are unchanged.
        $this->assertSame(-2.5, Floats::normalizeZero(-2.5));

        // Test that infinity values are unchanged.
        $this->assertSame(INF, Floats::normalizeZero(INF));
        $this->assertSame(-INF, Floats::normalizeZero(-INF));

        // Test that NAN is unchanged (NAN !== NAN, so use is_nan).
        $this->assertTrue(is_nan(Floats::normalizeZero(NAN)));
    }

    #endregion

    #region Method trunc() tests.

    /**
     * Test Floats::trunc() with positive values.
     */
    public function testTruncPositive(): void
    {
        $this->assertSame(3.0, Floats::trunc(3.7));
        $this->assertSame(3.0, Floats::trunc(3.2));
        $this->assertSame(3.0, Floats::trunc(3.0));
        $this->assertSame(0.0, Floats::trunc(0.9));
        $this->assertSame(100.0, Floats::trunc(100.999));
    }

    /**
     * Test Floats::trunc() with negative values.
     */
    public function testTruncNegative(): void
    {
        $this->assertSame(-3.0, Floats::trunc(-3.7));
        $this->assertSame(-3.0, Floats::trunc(-3.2));
        $this->assertSame(-3.0, Floats::trunc(-3.0));
        $this->assertSame(0.0, Floats::trunc(-0.9));
        $this->assertSame(-100.0, Floats::trunc(-100.999));
    }

    /**
     * Test Floats::trunc() with zero values.
     */
    public function testTruncZero(): void
    {
        $this->assertSame(0.0, Floats::trunc(0.0));
        $this->assertSame(0.0, Floats::trunc(-0.0));
        $this->assertSame(0.0, Floats::trunc(0.5));
        $this->assertSame(0.0, Floats::trunc(-0.5));
    }

    /**
     * Test Floats::trunc() with non-finite values.
     */
    public function testTruncNonFinite(): void
    {
        $this->assertSame(INF, Floats::trunc(INF));
        $this->assertSame(-INF, Floats::trunc(-INF));
        $this->assertTrue(is_nan(Floats::trunc(NAN)));
    }

    /**
     * Test Floats::trunc() differs from floor() for negative values.
     */
    public function testTruncDiffersFromFloor(): void
    {
        // floor() rounds toward -INF, Floats::trunc() rounds toward zero
        $this->assertSame(-3.0, Floats::trunc(-3.7));
        $this->assertSame(-4.0, floor(-3.7));

        $this->assertSame(-3.0, Floats::trunc(-3.2));
        $this->assertSame(-4.0, floor(-3.2));
    }

    #endregion

    #region Method frac() tests.

    /**
     * Test Floats::frac() with positive values.
     */
    public function testFracPositive(): void
    {
        $this->assertEqualsWithDelta(0.7, Floats::frac(3.7), 1e-10);
        $this->assertEqualsWithDelta(0.2, Floats::frac(3.2), 1e-10);
        $this->assertEqualsWithDelta(0.0, Floats::frac(3.0), 1e-10);
        $this->assertEqualsWithDelta(0.9, Floats::frac(0.9), 1e-10);
        $this->assertEqualsWithDelta(0.999, Floats::frac(100.999), 1e-10);
    }

    /**
     * Test Floats::frac() with negative values.
     */
    public function testFracNegative(): void
    {
        $this->assertEqualsWithDelta(-0.7, Floats::frac(-3.7), 1e-10);
        $this->assertEqualsWithDelta(-0.2, Floats::frac(-3.2), 1e-10);
        $this->assertEqualsWithDelta(0.0, Floats::frac(-3.0), 1e-10);
        $this->assertEqualsWithDelta(-0.9, Floats::frac(-0.9), 1e-10);
        $this->assertEqualsWithDelta(-0.999, Floats::frac(-100.999), 1e-10);
    }

    /**
     * Test Floats::frac() with zero values.
     */
    public function testFracZero(): void
    {
        $this->assertSame(0.0, Floats::frac(0.0));
        $this->assertSame(0.0, Floats::frac(-0.0));
    }

    /**
     * Test Floats::frac() with non-finite values.
     */
    public function testFracNonFinite(): void
    {
        // Infinity has no fractional part.
        $this->assertSame(0.0, Floats::frac(INF));
        $this->assertSame(0.0, Floats::frac(-INF));
        // NAN is still NAN.
        $this->assertTrue(is_nan(Floats::frac(NAN)));
    }

    /**
     * Test Floats::frac() satisfies the identity x = Floats::trunc(x) + Floats::frac(x).
     */
    public function testFracIdentity(): void
    {
        $testValues = [3.7, -3.7, 0.5, -0.5, 100.999, -100.999, 0.0, 42.0, -42.0];

        foreach ($testValues as $value) {
            $this->assertEqualsWithDelta(
                $value,
                Floats::trunc($value) + Floats::frac($value),
                1e-10,
                "Identity x = Floats::trunc(x) + Floats::frac(x) failed for x = $value"
            );
        }
    }

    #endregion

    #region Method wrap() tests.

    /**
     * Test wrap() with values already within the signed range.
     */
    public function testWrapSignedValuesInRange(): void
    {
        // Values already in (-180, 180] should remain unchanged.
        $this->assertSame(0.0, Floats::wrap(0.0, 360.0));
        $this->assertSame(45.0, Floats::wrap(45.0, 360.0));
        $this->assertSame(-45.0, Floats::wrap(-45.0, 360.0));
        $this->assertSame(179.0, Floats::wrap(179.0, 360.0));
        $this->assertSame(-179.0, Floats::wrap(-179.0, 360.0));
    }

    /**
     * Test wrap() with values already within the unsigned range.
     */
    public function testWrapUnsignedValuesInRange(): void
    {
        // Values already in [0, 360) should remain unchanged.
        $this->assertSame(0.0, Floats::wrap(0.0, 360.0, signed: false));
        $this->assertSame(45.0, Floats::wrap(45.0, 360.0, signed: false));
        $this->assertSame(270.0, Floats::wrap(270.0, 360.0, signed: false));
        $this->assertSame(359.0, Floats::wrap(359.0, 360.0, signed: false));
    }

    /**
     * Test wrap() signed boundary conditions.
     * Signed range is (-180, 180] so -180 is excluded and 180 is included.
     */
    public function testWrapSignedBoundaries(): void
    {
        // 180 is included in the signed range.
        $this->assertSame(180.0, Floats::wrap(180.0, 360.0));

        // -180 is excluded, should wrap to 180.
        $this->assertSame(180.0, Floats::wrap(-180.0, 360.0));

        // Values just inside the boundaries.
        $this->assertSame(179.9, Floats::wrap(179.9, 360.0));
        $this->assertSame(-179.9, Floats::wrap(-179.9, 360.0));
    }

    /**
     * Test wrap() unsigned boundary conditions.
     * Unsigned range is [0, 360) so 0 is included and 360 is excluded.
     */
    public function testWrapUnsignedBoundaries(): void
    {
        // 0 is included in the unsigned range.
        $this->assertSame(0.0, Floats::wrap(0.0, 360.0, signed: false));

        // 360 is excluded, should wrap to 0.
        $this->assertSame(0.0, Floats::wrap(360.0, 360.0, signed: false));

        // Values just inside the boundaries.
        $this->assertSame(0.1, Floats::wrap(0.1, 360.0, signed: false));
        $this->assertSame(359.9, Floats::wrap(359.9, 360.0, signed: false));
    }

    /**
     * Test wrap() with values requiring positive wrapping (signed).
     */
    public function testWrapSignedPositiveValues(): void
    {
        // Values > 180 should wrap into the negative range.
        $this->assertSame(-90.0, Floats::wrap(270.0, 360.0));
        $this->assertSame(0.0, Floats::wrap(360.0, 360.0));
        $this->assertSame(90.0, Floats::wrap(450.0, 360.0));
        $this->assertSame(180.0, Floats::wrap(540.0, 360.0));

        // Multiple full rotations.
        $this->assertSame(90.0, Floats::wrap(810.0, 360.0)); // 2*360 + 90
    }

    /**
     * Test wrap() with values requiring negative wrapping (signed).
     */
    public function testWrapSignedNegativeValues(): void
    {
        // Values < -180 should wrap into the positive range.
        $this->assertSame(90.0, Floats::wrap(-270.0, 360.0));
        $this->assertSame(0.0, Floats::wrap(-360.0, 360.0));
        $this->assertSame(-90.0, Floats::wrap(-450.0, 360.0));
        $this->assertSame(180.0, Floats::wrap(-540.0, 360.0));

        // Multiple full rotations.
        $this->assertSame(-90.0, Floats::wrap(-810.0, 360.0)); // -2*360 - 90
    }

    /**
     * Test wrap() with values requiring wrapping (unsigned).
     */
    public function testWrapUnsignedWrapping(): void
    {
        // Positive values >= 360.
        $this->assertSame(0.0, Floats::wrap(360.0, 360.0, signed: false));
        $this->assertSame(90.0, Floats::wrap(450.0, 360.0, signed: false));
        $this->assertSame(0.0, Floats::wrap(720.0, 360.0, signed: false));

        // Negative values should wrap to positive range.
        $this->assertSame(270.0, Floats::wrap(-90.0, 360.0, signed: false));
        $this->assertSame(180.0, Floats::wrap(-180.0, 360.0, signed: false));
        $this->assertSame(0.0, Floats::wrap(-360.0, 360.0, signed: false));
        $this->assertSame(270.0, Floats::wrap(-450.0, 360.0, signed: false));
    }

    /**
     * Test wrap() with radians (default unitsPerTurn).
     */
    public function testWrapRadians(): void
    {
        // Signed (default): range is (-π, π].
        $this->assertSame(0.0, Floats::wrap(0.0));
        $this->assertSame(M_PI, Floats::wrap(M_PI));
        $this->assertSame(M_PI, Floats::wrap(-M_PI));
        $this->assertEqualsWithDelta(0.0, Floats::wrap(M_TAU), 1e-10);
        $this->assertEqualsWithDelta(-M_PI / 2, Floats::wrap(3 * M_PI / 2), 1e-10);

        // Unsigned: range is [0, τ).
        $this->assertSame(0.0, Floats::wrap(0.0, signed: false));
        $this->assertSame(M_PI, Floats::wrap(M_PI, signed: false));
        $this->assertSame(M_PI, Floats::wrap(-M_PI, signed: false));
        $this->assertEqualsWithDelta(0.0, Floats::wrap(M_TAU, signed: false), 1e-10);
        $this->assertEqualsWithDelta(3 * M_PI / 2, Floats::wrap(-M_PI / 2, signed: false), 1e-10);
    }

    /**
     * Test wrap() with other unit systems.
     */
    public function testWrapOtherUnits(): void
    {
        // Gradians (400 per turn).
        $this->assertSame(0.0, Floats::wrap(0.0, 400.0));
        $this->assertSame(100.0, Floats::wrap(100.0, 400.0));
        $this->assertSame(-100.0, Floats::wrap(300.0, 400.0));
        $this->assertSame(0.0, Floats::wrap(400.0, 400.0));

        // Turns (1 per turn).
        $this->assertSame(0.0, Floats::wrap(0.0, 1.0));
        $this->assertSame(0.25, Floats::wrap(0.25, 1.0));
        $this->assertSame(-0.25, Floats::wrap(0.75, 1.0));
        $this->assertSame(0.0, Floats::wrap(1.0, 1.0));

        // Hours (24-hour clock, unsigned).
        $this->assertSame(0.0, Floats::wrap(0.0, 24.0, signed: false));
        $this->assertSame(6.0, Floats::wrap(6.0, 24.0, signed: false));
        $this->assertSame(18.0, Floats::wrap(18.0, 24.0, signed: false));
        $this->assertSame(1.0, Floats::wrap(25.0, 24.0, signed: false));
        $this->assertSame(21.0, Floats::wrap(-3.0, 24.0, signed: false));
    }

    /**
     * Test wrap() with a zero units per turn throws DomainException.
     */
    public function testWrapWithZeroUnitsPerTurnThrows(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid units per turn: 0.0. Must be finite and positive.');
        Floats::wrap(5.0, 0.0);
    }

    /**
     * Test wrap() with a negative units per turn throws DomainException.
     */
    public function testWrapWithNegativeUnitsPerTurnThrows(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid units per turn: -10.0. Must be finite and positive.');
        Floats::wrap(5.0, -10.0);
    }

    /**
     * Test wrap() with a NAN units per turn throws DomainException.
     */
    public function testWrapWithNanUnitsPerTurnThrows(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid units per turn: NAN. Must be finite and positive.');
        Floats::wrap(5.0, NAN);
    }

    /**
     * Test wrap() with an infinite units per turn throws DomainException.
     */
    public function testWrapWithInfiniteUnitsPerTurnThrows(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid units per turn: INF. Must be finite and positive.');
        Floats::wrap(5.0, INF);
    }

    #endregion
}
