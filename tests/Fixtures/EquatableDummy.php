<?php

declare(strict_types=1);

namespace OceanMoon\Core\Tests\Fixtures;

use InvalidArgumentException;
use OceanMoon\Core\Traits\Comparison\Equatable;

/**
 * Minimal class using the Equatable trait, for exercising it in isolation.
 */
final class EquatableDummy
{
    use Equatable;

    public function __construct(private readonly int $value)
    {
    }

    public function equal(mixed $other): bool
    {
        if (!$other instanceof self) {
            throw new InvalidArgumentException('Cannot compare EquatableDummy with ' . get_debug_type($other) . '.');
        }

        return $this->value === $other->value;
    }
}
