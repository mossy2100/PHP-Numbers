<?php

declare(strict_types=1);

namespace OceanMoon\Core\Tests\Fixtures;

use InvalidArgumentException;
use OceanMoon\Core\Floats;
use OceanMoon\Core\Traits\Comparison\ApproxComparable;

/**
 * Minimal class using the ApproxComparable trait, for exercising it in isolation.
 */
final class ApproxComparableDummy
{
    use ApproxComparable;

    public function __construct(private readonly float $value)
    {
    }

    public function compare(mixed $other): int
    {
        if (!$other instanceof self) {
            throw new InvalidArgumentException(
                'Cannot compare ApproxComparableDummy with ' . get_debug_type($other) . '.'
            );
        }

        return match (true) {
            $this->value < $other->value => -1,
            $this->value > $other->value => 1,
            default => 0,
        };
    }

    public function approxEqual(
        mixed $other,
        float $relTol = Floats::DEFAULT_RELATIVE_TOLERANCE,
        float $absTol = Floats::DEFAULT_ABSOLUTE_TOLERANCE
    ): bool {
        if (!$other instanceof self) {
            throw new InvalidArgumentException(
                'Cannot compare ApproxComparableDummy with ' . get_debug_type($other) . '.'
            );
        }

        return Floats::approxEqual($this->value, $other->value, $relTol, $absTol);
    }
}
