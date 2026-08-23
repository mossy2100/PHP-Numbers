<?php

declare(strict_types=1);

namespace OceanMoon\Core\Tests\Fixtures;

use Stringable;

/**
 * Test fixture implementing Stringable, for testing the Stringable fast path in to_string().
 */
class StringableThing implements Stringable
{
    public function __toString(): string
    {
        return 'custom';
    }
}
