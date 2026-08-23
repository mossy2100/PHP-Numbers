<?php

declare(strict_types=1);

namespace OceanMoon\Core\Tests\Fixtures;

use InvalidArgumentException;
use OceanMoon\Core\Floats;
use OceanMoon\Core\Traits\Comparison\ApproxEquatable;

/**
 * Minimal class using the ApproxEquatable trait, for exercising it in isolation.
 */
final class ApproxEquatableDummy
{
    use ApproxEquatable;

    public function __construct(private readonly float $value)
    {
    }

    public function equal(mixed $other): bool
    {
        if (!$other instanceof self) {
            throw new InvalidArgumentException(
                'Cannot compare ApproxEquatableDummy with ' . get_debug_type($other) . '.'
            );
        }

        return $this->value === $other->value;
    }

    public function approxEqual(
        mixed $other,
        float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
        float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
    ): bool {
        if (!$other instanceof self) {
            throw new InvalidArgumentException(
                'Cannot compare ApproxEquatableDummy with ' . get_debug_type($other) . '.'
            );
        }

        return Floats::approxEqual($this->value, $other->value, $relTol, $absTol);
    }
}
