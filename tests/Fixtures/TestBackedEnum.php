<?php

declare(strict_types=1);

namespace OceanMoon\Core\Tests\Fixtures;

/**
 * Backed test enum for stringifyEnum tests.
 */
enum TestBackedEnum: string
{
    case Alpha = 'a';

    case Beta = 'b';
}
