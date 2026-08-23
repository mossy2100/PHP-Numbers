<?php

declare(strict_types=1);

namespace OceanMoon\Core\Tests;

use DomainException;
use OceanMoon\Core\Numbers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Test class for Numbers utility class.
 */
#[CoversClass(Numbers::class)]
final class NumbersTest extends TestCase
{
    #region Method isNumber() tests.

    /**
     * Test Numbers::isNumber returns true for integers.
     */
    public function testIsNumberWithIntegers(): void
    {
        $this->assertTrue(Numbers::isNumber(0)); // @phpstan-ignore staticMethod.alreadyNarrowedType
        $this->assertTrue(Numbers::isNumber(42)); // @phpstan-ignore staticMethod.alreadyNarrowedType
        $this->assertTrue(Numbers::isNumber(-99)); // @phpstan-ignore staticMethod.alreadyNarrowedType
        $this->assertTrue(Numbers::isNumber(PHP_INT_MAX)); // @phpstan-ignore staticMethod.alreadyNarrowedType
        $this->assertTrue(Numbers::isNumber(PHP_INT_MIN)); // @phpstan-ignore staticMethod.alreadyNarrowedType
    }

    /**
     * Test Numbers::isNumber returns true for floats.
     */
    public function testIsNumberWithFloats(): void
    {
        $this->assertTrue(Numbers::isNumber(0.0)); // @phpstan-ignore staticMethod.alreadyNarrowedType
        $this->assertTrue(Numbers::isNumber(3.14)); // @phpstan-ignore staticMethod.alreadyNarrowedType
        $this->assertTrue(Numbers::isNumber(-2.5)); // @phpstan-ignore staticMethod.alreadyNarrowedType
        $this->assertTrue(Numbers::isNumber(1e10)); // @phpstan-ignore staticMethod.alreadyNarrowedType
        $this->assertTrue(Numbers::isNumber(PHP_FLOAT_MAX)); // @phpstan-ignore staticMethod.alreadyNarrowedType
        $this->assertTrue(Numbers::isNumber(PHP_FLOAT_MIN)); // @phpstan-ignore staticMethod.alreadyNarrowedType
        $this->assertTrue(Numbers::isNumber(PHP_FLOAT_EPSILON)); // @phpstan-ignore staticMethod.alreadyNarrowedType
    }

    /**
     * Test Numbers::isNumber returns true for special float values.
     */
    public function testIsNumberWithSpecialFloats(): void
    {
        $this->assertTrue(Numbers::isNumber(INF)); // @phpstan-ignore staticMethod.alreadyNarrowedType
        $this->assertTrue(Numbers::isNumber(-INF)); // @phpstan-ignore staticMethod.alreadyNarrowedType
        $this->assertTrue(Numbers::isNumber(NAN)); // @phpstan-ignore staticMethod.alreadyNarrowedType
        $this->assertTrue(Numbers::isNumber(-0.0)); // @phpstan-ignore staticMethod.alreadyNarrowedType
    }

    /**
     * Test Numbers::isNumber returns false for numeric strings.
     */
    public function testIsNumberWithNumericStrings(): void
    {
        $this->assertFalse(Numbers::isNumber('42')); // @phpstan-ignore staticMethod.impossibleType
        $this->assertFalse(Numbers::isNumber('3.14')); // @phpstan-ignore staticMethod.impossibleType
        $this->assertFalse(Numbers::isNumber('-99')); // @phpstan-ignore staticMethod.impossibleType
        $this->assertFalse(Numbers::isNumber('1e10')); // @phpstan-ignore staticMethod.impossibleType
        $this->assertFalse(Numbers::isNumber('0x1A')); // @phpstan-ignore staticMethod.impossibleType
    }

    /**
     * Test Numbers::isNumber returns false for non-numeric types.
     */
    public function testIsNumberWithNonNumericTypes(): void
    {
        $this->assertFalse(Numbers::isNumber('hello')); // @phpstan-ignore staticMethod.impossibleType
        $this->assertFalse(Numbers::isNumber('')); // @phpstan-ignore staticMethod.impossibleType
        $this->assertFalse(Numbers::isNumber(true)); // @phpstan-ignore staticMethod.impossibleType
        $this->assertFalse(Numbers::isNumber(false)); // @phpstan-ignore staticMethod.impossibleType
        $this->assertFalse(Numbers::isNumber(null)); // @phpstan-ignore staticMethod.impossibleType
        $this->assertFalse(Numbers::isNumber([])); // @phpstan-ignore staticMethod.impossibleType
        $this->assertFalse(Numbers::isNumber([1, 2])); // @phpstan-ignore staticMethod.impossibleType
        $this->assertFalse(Numbers::isNumber(new stdClass())); // @phpstan-ignore staticMethod.impossibleType
    }

    #endregion

    #region Method equal() tests.

    /**
     * Test equal with two equal integers.
     */
    public function testEqualWithEqualIntegers(): void
    {
        $this->assertTrue(Numbers::equal(5, 5));
        $this->assertTrue(Numbers::equal(0, 0));
        $this->assertTrue(Numbers::equal(-42, -42));
        $this->assertTrue(Numbers::equal(1000000, 1000000));
    }

    /**
     * Test equal with two different integers.
     */
    public function testEqualWithDifferentIntegers(): void
    {
        $this->assertFalse(Numbers::equal(5, 6));
        $this->assertFalse(Numbers::equal(0, 1));
        $this->assertFalse(Numbers::equal(-42, 42));
        $this->assertFalse(Numbers::equal(1000000, 1000001));
    }

    /**
     * Test equal with two equal floats.
     */
    public function testEqualWithEqualFloats(): void
    {
        $this->assertTrue(Numbers::equal(5.0, 5.0));
        $this->assertTrue(Numbers::equal(0.0, 0.0));
        $this->assertTrue(Numbers::equal(-42.5, -42.5));
        $this->assertTrue(Numbers::equal(1.23456789, 1.23456789));
    }

    /**
     * Test equal with two different floats.
     */
    public function testEqualWithDifferentFloats(): void
    {
        $this->assertFalse(Numbers::equal(5.0, 5.1));
        $this->assertFalse(Numbers::equal(0.0, 0.1));
        $this->assertFalse(Numbers::equal(-42.5, -42.6));
        $this->assertFalse(Numbers::equal(1.0, 1.0 + PHP_FLOAT_EPSILON));
    }

    /**
     * Test equal with mixed int and float (equal values).
     */
    public function testEqualWithMixedIntFloatEqual(): void
    {
        $this->assertTrue(Numbers::equal(5, 5.0));
        $this->assertTrue(Numbers::equal(5.0, 5));
        $this->assertTrue(Numbers::equal(0, 0.0));
        $this->assertTrue(Numbers::equal(0.0, 0));
        $this->assertTrue(Numbers::equal(-42, -42.0));
        $this->assertTrue(Numbers::equal(-42.0, -42));
    }

    /**
     * Test equal with mixed int and float (different values).
     */
    public function testEqualWithMixedIntFloatDifferent(): void
    {
        $this->assertFalse(Numbers::equal(5, 5.1));
        $this->assertFalse(Numbers::equal(5.1, 5));
        $this->assertFalse(Numbers::equal(0, 0.1));
        $this->assertFalse(Numbers::equal(0.1, 0));
    }

    /**
     * Test equal with positive and negative zero.
     */
    public function testEqualWithZeros(): void
    {
        $this->assertTrue(Numbers::equal(0, 0));
        $this->assertTrue(Numbers::equal(0.0, 0.0));
        $this->assertTrue(Numbers::equal(0, 0.0));
        $this->assertTrue(Numbers::equal(0.0, 0));
        $this->assertTrue(Numbers::equal(0.0, -0.0));
        $this->assertTrue(Numbers::equal(-0.0, 0.0));
    }

    /**
     * Test equal with special float values.
     */
    public function testEqualWithSpecialFloats(): void
    {
        // INF
        $this->assertTrue(Numbers::equal(INF, INF));
        $this->assertFalse(Numbers::equal(INF, -INF));
        $this->assertFalse(Numbers::equal(INF, 1.0));

        // -INF
        $this->assertTrue(Numbers::equal(-INF, -INF));
        $this->assertFalse(Numbers::equal(-INF, INF));
        $this->assertFalse(Numbers::equal(-INF, -1.0));

        // NAN (NAN !== NAN by IEEE 754)
        $this->assertFalse(Numbers::equal(NAN, NAN));
        $this->assertFalse(Numbers::equal(NAN, 1.0));
    }

    /**
     * Test equal with a large but exact int/float pair beyond Floats::MAX_SAFE_INT (2^53 - 1).
     * These are still exactly equal even though the int couldn't be losslessly round-tripped
     * through a float conversion in general -- Floats::isSafeInt() would be the wrong guard here.
     */
    public function testEqualWithLargeExactValues(): void
    {
        // 2^60, a power of two, so exactly representable as a float.
        $this->assertTrue(Numbers::equal(1152921504606846976, 1152921504606846976.0));
        $this->assertTrue(Numbers::equal(1152921504606846976.0, 1152921504606846976));
    }

    /**
     * Test equal with the int range boundaries. PHP_INT_MIN is a power of two, so it casts to
     * float exactly, but PHP_INT_MAX does not -- it rounds up to 2^63, which is outside the int
     * range and therefore never equal to any int.
     */
    public function testEqualWithIntBoundaries(): void
    {
        $this->assertTrue(Numbers::equal(PHP_INT_MIN, (float) PHP_INT_MIN));
        $this->assertFalse(Numbers::equal(PHP_INT_MAX, (float) PHP_INT_MAX));
    }

    /**
     * Test equal with a float outside the representable int range.
     */
    public function testEqualWithFloatOutsideIntRange(): void
    {
        $this->assertFalse(Numbers::equal(1, 1.0e30));
        $this->assertFalse(Numbers::equal(1.0e30, 1));
    }

    #endregion

    #region Method sign() tests.

    /**
     * Test sign detection with default behavior (zero for zero).
     */
    public function testSignDefault(): void
    {
        // Test positive numbers return 1.
        $this->assertSame(1, Numbers::sign(1));
        $this->assertSame(1, Numbers::sign(42));
        $this->assertSame(1, Numbers::sign(1.5));
        $this->assertSame(1, Numbers::sign(0.001));

        // Test negative numbers return -1.
        $this->assertSame(-1, Numbers::sign(-1));
        $this->assertSame(-1, Numbers::sign(-42));
        $this->assertSame(-1, Numbers::sign(-1.5));
        $this->assertSame(-1, Numbers::sign(-0.001));

        // Test zero returns 0 (default behavior).
        $this->assertSame(0, Numbers::sign(0));
        $this->assertSame(0, Numbers::sign(0.0));
        $this->assertSame(0, Numbers::sign(-0.0));

        // Test infinity values.
        $this->assertSame(1, Numbers::sign(INF));
        $this->assertSame(-1, Numbers::sign(-INF));
    }

    /**
     * Test sign detection with zeroForZero set to false.
     */
    public function testSignNoZeroForZero(): void
    {
        // Test positive numbers return 1.
        $this->assertSame(1, Numbers::sign(1, false));
        $this->assertSame(1, Numbers::sign(42.5, false));

        // Test negative numbers return -1.
        $this->assertSame(-1, Numbers::sign(-1, false));
        $this->assertSame(-1, Numbers::sign(-42.5, false));

        // Test integer zero returns 1 (positive zero).
        $this->assertSame(1, Numbers::sign(0, false));

        // Test positive float zero returns 1.
        $this->assertSame(1, Numbers::sign(0.0, false));

        // Test negative float zero returns -1.
        $this->assertSame(-1, Numbers::sign(-0.0, false));

        // Test infinity values.
        $this->assertSame(1, Numbers::sign(INF, false));
        $this->assertSame(-1, Numbers::sign(-INF, false));
    }

    /**
     * Test that sign throws DomainException when value is NAN.
     */
    public function testSignWithNanThrows(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot get sign of NAN.');
        Numbers::sign(NAN);
    }

    #endregion

    #region Method copySign() tests.

    /**
     * Test copying sign to positive numbers.
     */
    public function testCopySignToPositive(): void
    {
        // Test copying positive sign to positive number.
        $this->assertSame(5, Numbers::copySign(5, 10));
        $this->assertSame(5.0, Numbers::copySign(5.0, 10.0));

        // Test copying negative sign to positive number.
        $this->assertSame(-5, Numbers::copySign(5, -10));
        $this->assertSame(-5.0, Numbers::copySign(5.0, -10.0));

        // Test copying sign from zero to positive number.
        $this->assertSame(5, Numbers::copySign(5, 0));
        $this->assertSame(5.0, Numbers::copySign(5.0, 0.0));
        $this->assertSame(-5, Numbers::copySign(5, -0.0));

        // Test copying sign from infinity.
        $this->assertSame(5, Numbers::copySign(5, INF));
        $this->assertSame(-5, Numbers::copySign(5, -INF));
    }

    /**
     * Test copying sign to negative numbers.
     */
    public function testCopySignToNegative(): void
    {
        // Test copying positive sign to negative number.
        $this->assertSame(5, Numbers::copySign(-5, 10));
        $this->assertSame(5.0, Numbers::copySign(-5.0, 10.0));

        // Test copying negative sign to negative number.
        $this->assertSame(-5, Numbers::copySign(-5, -10));
        $this->assertSame(-5.0, Numbers::copySign(-5.0, -10.0));

        // Test copying sign from zero to negative number.
        $this->assertSame(5, Numbers::copySign(-5, 0));
        $this->assertSame(5.0, Numbers::copySign(-5.0, 0.0));
        $this->assertSame(-5, Numbers::copySign(-5, -0.0));
    }

    /**
     * Test copying sign to and from zero.
     */
    public function testCopySignWithZero(): void
    {
        // Test copying positive sign to zero.
        $this->assertSame(0, Numbers::copySign(0, 10));
        $this->assertSame(0.0, Numbers::copySign(0.0, 10));

        // Test copying negative sign to zero.
        $this->assertSame(0, Numbers::copySign(0, -10));
        $this->assertSame(-0.0, Numbers::copySign(0.0, -10));

        // Test copying sign from positive zero.
        $this->assertSame(5, Numbers::copySign(5, 0.0));

        // Test copying sign from negative zero.
        $this->assertSame(-5, Numbers::copySign(5, -0.0));
    }

    /**
     * Test copying sign with infinity values.
     */
    public function testCopySignWithInfinity(): void
    {
        // Test copying sign to infinity.
        $this->assertSame(INF, Numbers::copySign(INF, 10));
        $this->assertSame(-INF, Numbers::copySign(INF, -10));
        $this->assertSame(INF, Numbers::copySign(-INF, 10));
        $this->assertSame(-INF, Numbers::copySign(-INF, -10));

        // Test copying sign from infinity.
        $this->assertSame(5, Numbers::copySign(5, INF));
        $this->assertSame(-5, Numbers::copySign(5, -INF));
    }

    /**
     * Test copying sign to/from PHP_INT_MIN, whose magnitude can't be expressed as an int.
     */
    public function testCopySignWithIntMin(): void
    {
        // Negative sign: magnitude and sign are both already correct, so the result stays an int.
        $this->assertSame(PHP_INT_MIN, Numbers::copySign(PHP_INT_MIN, -10));

        // Positive sign: abs(PHP_INT_MIN) overflows to float, so the result is a float.
        $result = Numbers::copySign(PHP_INT_MIN, 10);
        $this->assertIsFloat($result);
        $this->assertSame(abs((float) PHP_INT_MIN), $result);
    }

    /**
     * Test that copySign throws DomainException when num is NAN.
     */
    public function testCopySignWithNanAsNum(): void
    {
        // Test that NAN as first parameter throws DomainException.
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot copy sign to or from NAN.');
        Numbers::copySign(NAN, 5);
    }

    /**
     * Test that copySign throws DomainException when sign_source is NAN.
     */
    public function testCopySignWithNanAsSignSource(): void
    {
        // Test that NAN as second parameter throws DomainException.
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot copy sign to or from NAN.');
        Numbers::copySign(5, NAN);
    }

    /**
     * Test that copySign throws DomainException when both parameters are NAN.
     */
    public function testCopySignWithBothNan(): void
    {
        // Test that NAN as both parameters throws DomainException.
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot copy sign to or from NAN.');
        Numbers::copySign(NAN, NAN);
    }

    /**
     * Test copySign preserves the type relationship.
     */
    public function testCopySignReturnType(): void
    {
        // Test that copySign with int parameter returns int.
        $result = Numbers::copySign(5, 10);
        $this->assertIsInt($result);

        $result = Numbers::copySign(5, -10);
        $this->assertIsInt($result);

        // Test that copySign with float parameter returns float.
        $result = Numbers::copySign(5.0, 10);
        $this->assertIsFloat($result);

        $result = Numbers::copySign(5.0, -10.0);
        $this->assertIsFloat($result);
    }

    #endregion
}
