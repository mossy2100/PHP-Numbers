<?php

declare(strict_types=1);

namespace OceanMoon\Core;

use DomainException;

/**
 * Container for general number-related utility methods.
 */
final class Numbers
{
    #region Constructor

    /**
     * Private constructor to prevent instantiation.
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    #endregion

    #region Inspection methods

    /**
     * Check if a value is a number, i.e. an integer or a float.
     * This varies from is_numeric(), which also returns true for numeric strings.
     *
     * @param mixed $value The value to check.
     * @return bool True if the value is a number, false otherwise.
     * @phpstan-assert-if-true int|float $value
     */
    public static function isNumber(mixed $value): bool
    {
        return is_int($value) || is_float($value);
    }

    #endregion

    #region Comparison methods

    /**
     * Check if two numbers are exactly equal, regardless of whether they're int or float.
     *
     * This method is useful for equality comparison when working with values that can be ints or floats. It serves
     * several purposes:
     * 1. Avoids numeric strings being converted to numbers and compared as such.
     * 2. Silences IDE warnings about using ==/!= instead of ===/!==.
     * 3. Avoids integers being compared as equal that aren't. Because integers have 64 bits of precision and floats
     * only have 53, a comparison like (float) $a === (float) $b can return true for integers that are different but
     * convert to the same float.
     *
     * Unlike ==, this never coerces types-as-equal by accident: two ints are compared normally as ints, and two floats
     * are compared normally as floats (so NAN never equals anything, including itself, and -0.0 equals 0.0). A mixed
     * int/float pair is equal only if the float represents that exact integer losslessly, within the valid range for
     * integers (PHP_INT_MIN..PHP_INT_MAX).
     *
     * @param int|float $a The first number.
     * @param int|float $b The second number.
     * @return bool True if the two numbers are equal, false otherwise.
     */
    public static function equal(int|float $a, int|float $b): bool
    {
        // If they have the same type, compare using strict equality.
        if (Types::same($a, $b)) {
            return $a === $b;
        }

        // Mixed: one int, one float. A float can only equal an int if it converts to one losslessly.
        $i = is_int($a) ? $a : $b;
        $f = is_float($a) ? $a : $b;

        try {
            return Floats::toInt($f) === $i;
        } catch (DomainException) {
            return false;
        }
    }

    #endregion

    #region Sign methods

    /**
     * Get the sign of a number.
     *
     * This method has two modes of operation, determined by the $zeroForZero parameter.
     * In either mode, the method will return 1 for positive numbers and -1 for negative numbers.
     * 1. The default mode (when $zeroForZero is true) will return 0 when $value equals 0, -0.0, or +0.0.
     * 2. The alternate mode (when $zeroForZero is false) will return -1 for the special float value -0.0, or 1 for
     *    int 0 or float +0.0.
     *
     * @param int|float $value The number to check.
     * @param bool $zeroForZero If true (default), return 0 when $value equals 0. If false, return 1 or -1, indicating
     * the sign of the zero.
     * @return int The sign of the $value argument (-1, 0, or 1).
     * @throws DomainException If $value is NAN.
     */
    public static function sign(int|float $value, bool $zeroForZero = true): int
    {
        // Guard. This method won't work for NAN, which doesn't have a sign.
        if (is_nan($value)) {
            throw new DomainException('Cannot get sign of NAN.');
        }

        // Check for positive.
        if ($value > 0) {
            return 1;
        }

        // Check for negative.
        if ($value < 0) {
            return -1;
        }

        // Value is 0. Return the default result if requested.
        if ($zeroForZero) {
            return 0;
        }

        // Return the sign of the zero.
        return is_float($value) && Floats::isNegativeZero($value) ? -1 : 1;
    }

    /**
     * Copy the sign of one number to another.
     *
     * If $num is integer PHP_INT_MIN and $signSource is positive, the result will be a float, because abs(PHP_INT_MIN)
     * can't be expressed as an int.
     *
     * In all other cases, the result will have the same type as $num.
     *
     * @param int|float $num The number whose magnitude to use.
     * @param int|float $signSource The number whose sign to copy.
     * @return int|float The magnitude of $num with the sign of $signSource.
     * @throws DomainException If NAN is passed as either parameter.
     */
    public static function copySign(int|float $num, int|float $signSource): int|float
    {
        // Guard. This method won't work for NAN, which doesn't have a sign.
        if (is_nan($num) || is_nan($signSource)) {
            throw new DomainException('Cannot copy sign to or from NAN.');
        }

        $sign = self::sign($signSource, false);

        // If the num is PHP_INT_MIN and sign is negative, return an int by avoiding the call to abs().
        if ($num === PHP_INT_MIN && $sign === -1) {
            return PHP_INT_MIN;
        }

        return abs($num) * $sign;
    }

    #endregion
}
