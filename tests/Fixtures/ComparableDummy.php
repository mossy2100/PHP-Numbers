<?php

declare(strict_types=1);

namespace OceanMoon\Core\Tests\Fixtures;

use InvalidArgumentException;
use OceanMoon\Core\Traits\Comparison\Comparable;

/**
 * Minimal class using the Comparable trait, for exercising it in isolation.
 */
final class ComparableDummy
{
    use Comparable;

    public function __construct(private readonly int $value)
    {
    }

    public function compare(mixed $other): int
    {
        if (!$other instanceof self) {
            throw new InvalidArgumentException(
                'Cannot compare ComparableDummy with ' . get_debug_type($other) . '.'
            );
        }

        return match (true) {
            $this->value < $other->value => -1,
            $this->value > $other->value => 1,
            default => 0,
        };
    }
}
