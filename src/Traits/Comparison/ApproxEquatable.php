<?php

declare(strict_types=1);

namespace OceanMoon\Core\Traits\Comparison;

use OceanMoon\Core\Floats;

/**
 * Trait for objects that support approximate equality comparison with configurable tolerances.
 *
 * This trait extends Equatable by adding an approxEqual() method that allows comparison within specified absolute
 * and relative tolerances. This is essential for value types that contain floating-point components (like Complex
 * numbers or Quantities), where exact equality comparison may fail due to floating-point precision limitations.
 *
 * The trait uses Equatable via composition, so classes using ApproxEquatable get both exact equality (equal()) and
 * approximate equality (approxEqual()) methods.
 *
 * Implementations should use the same tolerance algorithm as Floats::approxEqual():
 * 1. Check absolute tolerance first (useful for values near zero)
 * 2. If that fails, check relative tolerance (scales with magnitude)
 *
 * @see Equatable The base equality trait this includes.
 * @see ApproxComparable For types with both ordering and approximate equality.
 * @see Floats::approxEqual() The algorithm to use for tolerance checking.
 */
trait ApproxEquatable
{
    use Equatable;

    /**
     * Check if this object approximately equals another within specified tolerances.
     *
     * This method uses a combined absolute and relative tolerance approach, matching the algorithm in
     * Floats::approxEqual(). The absolute tolerance is checked first (useful for comparisons near zero), and if
     * that fails, the relative tolerance is checked (which scales with the magnitude of the values).
     *
     * To compare using only absolute difference, set $relTol to 0.0.
     * To compare using only relative difference, set $absTol to 0.0.
     *
     * The parameter is typed as mixed rather than self; see Equatable::equal() for why.
     *
     * @param mixed $other The value to compare with.
     * @param float $relTol The maximum allowed relative difference (default: 1e-9).
     * @param float $absTol The maximum allowed absolute difference (default: PHP_FLOAT_EPSILON).
     * @return bool True if the values are equal within the given tolerances, false otherwise.
     * @see Floats::approxEqual() For the tolerance algorithm details.
     */
    abstract public function approxEqual(
        mixed $other,
        float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
        float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
    ): bool;
}
