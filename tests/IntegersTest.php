<?php

declare(strict_types=1);

namespace OceanMoon\Core\Tests;

use BadMethodCallException;
use DomainException;
use OceanMoon\Core\Exceptions\FormatException;
use OceanMoon\Core\Integers;
use OverflowException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Integers utility class.
 */
#[CoversClass(Integers::class)]
final class IntegersTest extends TestCase
{
    #region Method add() tests.

    /**
     * Test addition of integers without overflow.
     */
    public function testAdd(): void
    {
        // Test basic addition.
        $this->assertSame(5, Integers::add(2, 3));
        $this->assertSame(0, Integers::add(0, 0));

        // Test addition with negative numbers.
        $this->assertSame(-5, Integers::add(-2, -3));
        $this->assertSame(1, Integers::add(-2, 3));
        $this->assertSame(-1, Integers::add(2, -3));

        // Test addition with zero.
        $this->assertSame(10, Integers::add(10, 0));
        $this->assertSame(-10, Integers::add(0, -10));

        // Test large numbers that don't overflow.
        $this->assertSame(1000000, Integers::add(500000, 500000));
    }

    /**
     * Test addition overflow detection.
     */
    public function testAddOverflow(): void
    {
        // Test positive overflow.
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Overflow in integer addition.');
        Integers::add(PHP_INT_MAX, 1);
    }

    /**
     * Test addition negative overflow detection.
     */
    public function testAddNegativeOverflow(): void
    {
        // Test negative overflow.
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Overflow in integer addition.');
        Integers::add(PHP_INT_MIN, -1);
    }

    #endregion

    #region Method sub() tests.

    /**
     * Test subtraction of integers without overflow.
     */
    public function testSub(): void
    {
        // Test basic subtraction.
        $this->assertSame(1, Integers::sub(3, 2));
        $this->assertSame(0, Integers::sub(0, 0));

        // Test subtraction with negative numbers.
        $this->assertSame(1, Integers::sub(-2, -3));
        $this->assertSame(-5, Integers::sub(-2, 3));
        $this->assertSame(5, Integers::sub(2, -3));

        // Test subtraction with zero.
        $this->assertSame(10, Integers::sub(10, 0));
        $this->assertSame(-10, Integers::sub(0, 10));

        // Test large numbers that don't overflow.
        $this->assertSame(0, Integers::sub(500000, 500000));
    }

    /**
     * Test subtraction overflow detection.
     */
    public function testSubOverflow(): void
    {
        // Test positive overflow (subtracting a large negative number).
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Overflow in integer subtraction.');
        Integers::sub(PHP_INT_MAX, -1);
    }

    /**
     * Test subtraction negative overflow detection.
     */
    public function testSubNegativeOverflow(): void
    {
        // Test negative overflow (subtracting a large positive number from minimum).
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Overflow in integer subtraction.');
        Integers::sub(PHP_INT_MIN, 1);
    }

    #endregion

    #region Method mul() tests.

    /**
     * Test multiplication of integers without overflow.
     */
    public function testMul(): void
    {
        // Test basic multiplication.
        $this->assertSame(6, Integers::mul(2, 3));
        $this->assertSame(0, Integers::mul(0, 0));

        // Test multiplication with negative numbers.
        $this->assertSame(6, Integers::mul(-2, -3));
        $this->assertSame(-6, Integers::mul(-2, 3));
        $this->assertSame(-6, Integers::mul(2, -3));

        // Test multiplication with zero.
        $this->assertSame(0, Integers::mul(10, 0));
        $this->assertSame(0, Integers::mul(0, 10));

        // Test multiplication with one.
        $this->assertSame(10, Integers::mul(10, 1));
        $this->assertSame(-10, Integers::mul(-10, 1));

        // Test large numbers that don't overflow.
        $this->assertSame(1000000, Integers::mul(1000, 1000));
    }

    /**
     * Test multiplication overflow detection.
     */
    public function testMulOverflow(): void
    {
        // Test positive overflow.
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Overflow in integer multiplication.');
        Integers::mul(PHP_INT_MAX, 2);
    }

    /**
     * Test multiplication negative overflow detection.
     */
    public function testMulNegativeOverflow(): void
    {
        // Test negative overflow.
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Overflow in integer multiplication.');
        Integers::mul(PHP_INT_MAX, -2);
    }

    #endregion

    #region Method pow() tests.

    /**
     * Test exponentiation of integers without overflow.
     */
    public function testPow(): void
    {
        // Test basic exponentiation.
        $this->assertSame(8, Integers::pow(2, 3));
        $this->assertSame(1, Integers::pow(5, 0));
        $this->assertSame(5, Integers::pow(5, 1));

        // Test with zero base.
        $this->assertSame(0, Integers::pow(0, 5));
        $this->assertSame(1, Integers::pow(0, 0));

        // Test with negative base.
        $this->assertSame(-8, Integers::pow(-2, 3));
        $this->assertSame(16, Integers::pow(-2, 4));
        $this->assertSame(1, Integers::pow(-5, 0));

        // Test with one.
        $this->assertSame(1, Integers::pow(1, 100));
        $this->assertSame(1, Integers::pow(-1, 0));
        $this->assertSame(-1, Integers::pow(-1, 1));

        // Test larger calculations that don't overflow.
        $this->assertSame(1024, Integers::pow(2, 10));
    }

    /**
     * Test exponentiation with negative exponent causing underflow.
     */
    public function testPowNegativeExponentUnderflow(): void
    {
        // Test that negative exponents throw DomainException.
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid exponent: -1. Must not be negative.');
        Integers::pow(2, -1);
    }

    /**
     * Test exponentiation overflow detection.
     */
    public function testPowOverflow(): void
    {
        // Test positive overflow.
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Overflow in integer exponentiation.');
        Integers::pow(PHP_INT_MAX, 2);
    }

    /**
     * Test exponentiation with large exponent causing overflow.
     */
    public function testPowLargeExponentOverflow(): void
    {
        // Test overflow with large exponent.
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Overflow in integer exponentiation.');
        Integers::pow(10, 100);
    }

    #endregion

    #region Method gcd() tests.

    /**
     * Test GCD calculation with two integers.
     */
    public function testGcdTwoIntegers(): void
    {
        // Test basic GCD.
        $this->assertSame(6, Integers::gcd(12, 18));
        $this->assertSame(1, Integers::gcd(17, 19));
        $this->assertSame(5, Integers::gcd(5, 10));

        // Test with same numbers.
        $this->assertSame(7, Integers::gcd(7, 7));

        // Test with one being zero.
        $this->assertSame(5, Integers::gcd(5, 0));
        $this->assertSame(5, Integers::gcd(0, 5));

        // Test with both being zero.
        $this->assertSame(0, Integers::gcd(0, 0));

        // Test with negative numbers (GCD uses absolute values).
        $this->assertSame(6, Integers::gcd(-12, 18));
        $this->assertSame(6, Integers::gcd(12, -18));
        $this->assertSame(6, Integers::gcd(-12, -18));

        // Test with one being one.
        $this->assertSame(1, Integers::gcd(1, 100));
    }

    /**
     * Test GCD calculation with multiple integers.
     */
    public function testGcdMultipleIntegers(): void
    {
        // Test with three integers.
        $this->assertSame(6, Integers::gcd(12, 18, 24));
        $this->assertSame(1, Integers::gcd(10, 15, 22));

        // Test with four integers.
        $this->assertSame(4, Integers::gcd(8, 12, 16, 20));

        // Test with five integers.
        $this->assertSame(5, Integers::gcd(10, 15, 20, 25, 30));

        // Test with mixed positive and negative.
        $this->assertSame(3, Integers::gcd(-9, 12, -15));
    }

    /**
     * Test GCD calculation with single integer.
     */
    public function testGcdSingleInteger(): void
    {
        // Test with single positive integer.
        $this->assertSame(42, Integers::gcd(42));

        // Test with single negative integer.
        $this->assertSame(42, Integers::gcd(-42));

        // Test with zero.
        $this->assertSame(0, Integers::gcd(0));
    }

    /**
     * Test GCD with no arguments throws error.
     */
    public function testGcdNoArguments(): void
    {
        // Test that calling GCD with no arguments throws BadMethodCallException.
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('At least one integer is required.');
        Integers::gcd();
    }

    /**
     * Test GCD with large numbers.
     */
    public function testGcdLargeNumbers(): void
    {
        // Test GCD with large coprime numbers.
        $this->assertSame(1, Integers::gcd(1000000007, 1000000009));

        // Test GCD with large numbers having common factors.
        $this->assertSame(3000, Integers::gcd(123000, 456000));
    }

    /**
     * Test GCD with PHP_INT_MIN alongside another value computes the correct power-of-two result, rather than just
     * treating PHP_INT_MIN as an error case. PHP_INT_MIN's magnitude (2^63) has 2 as its only prime factor, so the
     * result is always a power of two: 2 raised to the lower of 63 and the other value's own power-of-two factor.
     */
    public function testGcdWithPhpIntMinAndOtherValue(): void
    {
        // 5 is odd (2^0), so the shared power of two is 2^0 = 1.
        $this->assertSame(1, Integers::gcd(PHP_INT_MIN, 5));
        $this->assertSame(1, Integers::gcd(5, PHP_INT_MIN));

        // 6 = 2 * 3 (2^1), so the shared power of two is 2^1 = 2.
        $this->assertSame(2, Integers::gcd(PHP_INT_MIN, 6));

        // 8 = 2^3, so the shared power of two is 2^3 = 8.
        $this->assertSame(8, Integers::gcd(PHP_INT_MIN, 8));
    }

    /**
     * Test GCD with PHP_INT_MIN and multiple other values uses the lowest power of two among them.
     */
    public function testGcdWithPhpIntMinAndMultipleOtherValues(): void
    {
        // 8 = 2^3 and 12 = 2^2 * 3, so the lowest shared power of two is 2^2 = 4.
        $this->assertSame(4, Integers::gcd(PHP_INT_MIN, 8, 12));

        // A second PHP_INT_MIN doesn't change anything: it still leaves 8 = 2^3 as the constraint.
        $this->assertSame(8, Integers::gcd(PHP_INT_MIN, PHP_INT_MIN, 8));

        // Zero doesn't constrain the result at all (gcd(a, 0) = a), so it's as if it weren't there.
        $this->assertSame(8, Integers::gcd(PHP_INT_MIN, 0, 8));
    }

    /**
     * Test GCD throws OverflowException when the true result is PHP_INT_MIN's own magnitude (2^63), which can't be
     * represented as an int. This only happens when every argument is PHP_INT_MIN or 0, since anything else has a
     * smaller magnitude and would reduce the result below 2^63.
     */
    public function testGcdWithOnlyPhpIntMinAndZeroesThrows(): void
    {
        $this->expectException(OverflowException::class);
        $this->expectExceptionMessage('Cannot compute GCD. Result (2^63) exceeds the range of an int.');
        Integers::gcd(PHP_INT_MIN);
    }

    /**
     * Test GCD throws OverflowException with PHP_INT_MIN combined with zero, which doesn't constrain the result.
     */
    public function testGcdWithPhpIntMinAndZeroThrows(): void
    {
        $this->expectException(OverflowException::class);
        Integers::gcd(PHP_INT_MIN, 0);
    }

    /**
     * Test GCD throws OverflowException with two PHP_INT_MIN values and no other constraint.
     */
    public function testGcdWithTwoPhpIntMinValuesThrows(): void
    {
        $this->expectException(OverflowException::class);
        Integers::gcd(PHP_INT_MIN, PHP_INT_MIN);
    }

    #endregion

    #region Method toSubscript() tests.

    /**
     * Test toSubscript with positive integer.
     */
    public function testToSubscriptPositive(): void
    {
        $this->assertSame('₁₂₃', Integers::toSubscript(123));
        $this->assertSame('₀', Integers::toSubscript(0));
        $this->assertSame('₉₈₇₆₅₄₃₂₁₀', Integers::toSubscript(9876543210));
    }

    /**
     * Test toSubscript with negative integer.
     */
    public function testToSubscriptNegative(): void
    {
        $this->assertSame('₋₁₂₃', Integers::toSubscript(-123));
        $this->assertSame('₋₁', Integers::toSubscript(-1));
    }

    #endregion

    #region Method toSuperscript() tests.

    /**
     * Test toSuperscript with positive integer.
     */
    public function testToSuperscriptPositive(): void
    {
        $this->assertSame('¹²³', Integers::toSuperscript(123));
        $this->assertSame('⁰', Integers::toSuperscript(0));
        $this->assertSame('⁹⁸⁷⁶⁵⁴³²¹⁰', Integers::toSuperscript(9876543210));
    }

    /**
     * Test toSuperscript with negative integer.
     */
    public function testToSuperscriptNegative(): void
    {
        $this->assertSame('⁻¹²³', Integers::toSuperscript(-123));
        $this->assertSame('⁻¹', Integers::toSuperscript(-1));
    }

    #endregion

    #region Method isSubscript() tests.

    /**
     * Test isSubscript with valid subscript strings.
     */
    public function testIsSubscriptValid(): void
    {
        // Positive integers.
        $this->assertTrue(Integers::isSubscript('₁₂₃'));
        $this->assertTrue(Integers::isSubscript('₀'));
        $this->assertTrue(Integers::isSubscript('₉₈₇₆₅₄₃₂₁₀'));

        // Negative integers.
        $this->assertTrue(Integers::isSubscript('₋₁₂₃'));
        $this->assertTrue(Integers::isSubscript('₋₁'));
        $this->assertTrue(Integers::isSubscript('₋₀'));
    }

    /**
     * Test isSubscript with invalid strings.
     */
    public function testIsSubscriptInvalid(): void
    {
        // Empty string.
        $this->assertFalse(Integers::isSubscript(''));

        // Regular digits.
        $this->assertFalse(Integers::isSubscript('123'));
        $this->assertFalse(Integers::isSubscript('-123'));

        // Superscript characters.
        $this->assertFalse(Integers::isSubscript('¹²³'));

        // Mixed subscript and regular.
        $this->assertFalse(Integers::isSubscript('₁2₃'));

        // Mixed subscript and superscript.
        $this->assertFalse(Integers::isSubscript('₁²₃'));

        // Just minus sign.
        $this->assertFalse(Integers::isSubscript('₋'));

        // Letters.
        $this->assertFalse(Integers::isSubscript('abc'));

        // Minus sign in wrong position.
        $this->assertFalse(Integers::isSubscript('₁₋₂'));
    }

    #endregion

    #region Method isSuperscript() tests.

    /**
     * Test isSuperscript with valid superscript strings.
     */
    public function testIsSuperscriptValid(): void
    {
        // Positive integers.
        $this->assertTrue(Integers::isSuperscript('¹²³'));
        $this->assertTrue(Integers::isSuperscript('⁰'));
        $this->assertTrue(Integers::isSuperscript('⁹⁸⁷⁶⁵⁴³²¹⁰'));

        // Negative integers.
        $this->assertTrue(Integers::isSuperscript('⁻¹²³'));
        $this->assertTrue(Integers::isSuperscript('⁻¹'));
        $this->assertTrue(Integers::isSuperscript('⁻⁰'));
    }

    /**
     * Test isSuperscript with invalid strings.
     */
    public function testIsSuperscriptInvalid(): void
    {
        // Empty string.
        $this->assertFalse(Integers::isSuperscript(''));

        // Regular digits.
        $this->assertFalse(Integers::isSuperscript('123'));
        $this->assertFalse(Integers::isSuperscript('-123'));

        // Subscript characters.
        $this->assertFalse(Integers::isSuperscript('₁₂₃'));

        // Mixed superscript and regular.
        $this->assertFalse(Integers::isSuperscript('¹2³'));

        // Mixed superscript and subscript.
        $this->assertFalse(Integers::isSuperscript('¹₂³'));

        // Just minus sign.
        $this->assertFalse(Integers::isSuperscript('⁻'));

        // Letters.
        $this->assertFalse(Integers::isSuperscript('abc'));

        // Minus sign in wrong position.
        $this->assertFalse(Integers::isSuperscript('¹⁻²'));
    }

    #endregion

    #region Method fromSubscript() tests.

    /**
     * Test fromSubscript with valid subscript strings.
     */
    public function testFromSubscriptValid(): void
    {
        // Positive integers.
        $this->assertSame(123, Integers::fromSubscript('₁₂₃'));
        $this->assertSame(0, Integers::fromSubscript('₀'));
        $this->assertSame(9876543210, Integers::fromSubscript('₉₈₇₆₅₄₃₂₁₀'));

        // Negative integers.
        $this->assertSame(-123, Integers::fromSubscript('₋₁₂₃'));
        $this->assertSame(-1, Integers::fromSubscript('₋₁'));

        // Single digit.
        $this->assertSame(5, Integers::fromSubscript('₅'));
    }

    /**
     * Test fromSubscript throws exception for invalid characters.
     */
    public function testFromSubscriptInvalidCharacter(): void
    {
        $this->expectException(FormatException::class);
        $this->expectExceptionMessage('Invalid subscript character');
        Integers::fromSubscript('₁2₃');
    }

    /**
     * Test fromSubscript throws exception for superscript characters.
     */
    public function testFromSubscriptSuperscriptCharacter(): void
    {
        $this->expectException(FormatException::class);
        $this->expectExceptionMessage('Invalid subscript character');
        Integers::fromSubscript('¹²³');
    }

    /**
     * Test fromSubscript throws exception for regular digits.
     */
    public function testFromSubscriptRegularDigits(): void
    {
        $this->expectException(FormatException::class);
        $this->expectExceptionMessage('Invalid subscript character');
        Integers::fromSubscript('123');
    }

    /**
     * Test fromSubscript throws exception for an empty string.
     */
    public function testFromSubscriptEmptyString(): void
    {
        $this->expectException(FormatException::class);
        $this->expectExceptionMessage('does not represent an integer');
        Integers::fromSubscript('');
    }

    /**
     * Test fromSubscript throws exception for a lone minus sign with no digits.
     */
    public function testFromSubscriptLoneMinusSign(): void
    {
        $this->expectException(FormatException::class);
        $this->expectExceptionMessage('does not represent an integer');
        Integers::fromSubscript('₋');
    }

    /**
     * Test fromSubscript throws OverflowException instead of silently saturating for out-of-range values.
     */
    public function testFromSubscriptOverflow(): void
    {
        $this->expectException(OverflowException::class);
        Integers::fromSubscript('₉₉₉₉₉₉₉₉₉₉₉₉₉₉₉₉₉₉₉₉');
    }

    /**
     * Test fromSubscript at the PHP_INT_MIN/PHP_INT_MAX boundaries.
     */
    public function testFromSubscriptIntBoundaries(): void
    {
        $this->assertSame(PHP_INT_MAX, Integers::fromSubscript(Integers::toSubscript(PHP_INT_MAX)));
        $this->assertSame(PHP_INT_MIN, Integers::fromSubscript(Integers::toSubscript(PHP_INT_MIN)));
    }

    #endregion

    #region Method fromSuperscript() tests.

    /**
     * Test fromSuperscript with valid superscript strings.
     */
    public function testFromSuperscriptValid(): void
    {
        // Positive integers.
        $this->assertSame(123, Integers::fromSuperscript('¹²³'));
        $this->assertSame(0, Integers::fromSuperscript('⁰'));
        $this->assertSame(9876543210, Integers::fromSuperscript('⁹⁸⁷⁶⁵⁴³²¹⁰'));

        // Negative integers.
        $this->assertSame(-123, Integers::fromSuperscript('⁻¹²³'));
        $this->assertSame(-1, Integers::fromSuperscript('⁻¹'));

        // Single digit.
        $this->assertSame(5, Integers::fromSuperscript('⁵'));
    }

    /**
     * Test fromSuperscript throws exception for invalid characters.
     */
    public function testFromSuperscriptInvalidCharacter(): void
    {
        $this->expectException(FormatException::class);
        $this->expectExceptionMessage('Invalid superscript character');
        Integers::fromSuperscript('¹2³');
    }

    /**
     * Test fromSuperscript throws exception for subscript characters.
     */
    public function testFromSuperscriptSubscriptCharacter(): void
    {
        $this->expectException(FormatException::class);
        $this->expectExceptionMessage('Invalid superscript character');
        Integers::fromSuperscript('₁₂₃');
    }

    /**
     * Test fromSuperscript throws exception for regular digits.
     */
    public function testFromSuperscriptRegularDigits(): void
    {
        $this->expectException(FormatException::class);
        $this->expectExceptionMessage('Invalid superscript character');
        Integers::fromSuperscript('123');
    }

    /**
     * Test fromSuperscript throws exception for an empty string.
     */
    public function testFromSuperscriptEmptyString(): void
    {
        $this->expectException(FormatException::class);
        $this->expectExceptionMessage('does not represent an integer');
        Integers::fromSuperscript('');
    }

    /**
     * Test fromSuperscript throws exception for a lone minus sign with no digits.
     */
    public function testFromSuperscriptLoneMinusSign(): void
    {
        $this->expectException(FormatException::class);
        $this->expectExceptionMessage('does not represent an integer');
        Integers::fromSuperscript('⁻');
    }

    /**
     * Test fromSuperscript throws OverflowException instead of silently saturating for out-of-range values.
     */
    public function testFromSuperscriptOverflow(): void
    {
        $this->expectException(OverflowException::class);
        Integers::fromSuperscript('⁹⁹⁹⁹⁹⁹⁹⁹⁹⁹⁹⁹⁹⁹⁹⁹⁹⁹⁹');
    }

    /**
     * Test fromSuperscript at the PHP_INT_MIN/PHP_INT_MAX boundaries.
     */
    public function testFromSuperscriptIntBoundaries(): void
    {
        $this->assertSame(PHP_INT_MAX, Integers::fromSuperscript(Integers::toSuperscript(PHP_INT_MAX)));
        $this->assertSame(PHP_INT_MIN, Integers::fromSuperscript(Integers::toSuperscript(PHP_INT_MIN)));
    }

    #endregion

    #region Round-trip tests.

    /**
     * Test round-trip conversion: toSubscript then fromSubscript.
     */
    public function testSubscriptRoundTrip(): void
    {
        $values = [0, 1, -1, 123, -456, 9876543210];
        foreach ($values as $value) {
            $subscript = Integers::toSubscript($value);
            $result = Integers::fromSubscript($subscript);
            $this->assertSame($value, $result);
        }
    }

    /**
     * Test round-trip conversion: toSuperscript then fromSuperscript.
     */
    public function testSuperscriptRoundTrip(): void
    {
        $values = [0, 1, -1, 123, -456, 9876543210];
        foreach ($values as $value) {
            $superscript = Integers::toSuperscript($value);
            $result = Integers::fromSuperscript($superscript);
            $this->assertSame($value, $result);
        }
    }

    /**
     * Test that fromSubscript() and fromSuperscript() give correct results when interleaved.
     *
     * Regression test: fromSubscript() and fromSuperscript() share a private helper with a character-map cache
     * keyed by conversion style. Calling them in alternating order previously returned stale results from
     * whichever character map was cached first.
     */
    public function testSubscriptAndSuperscriptInterleaved(): void
    {
        $this->assertSame(123, Integers::fromSuperscript('¹²³'));
        $this->assertSame(456, Integers::fromSubscript('₄₅₆'));
        $this->assertSame(-789, Integers::fromSuperscript('⁻⁷⁸⁹'));
        $this->assertSame(-321, Integers::fromSubscript('₋₃₂₁'));
    }

    #endregion
}
