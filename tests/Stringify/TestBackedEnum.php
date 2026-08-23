<?php

declare(strict_types=1);

namespace OceanMoon\Core\Tests\Stringify;

/**
 * Backed test enum for stringifyEnum tests.
 */
enum TestBackedEnum: string
{
    case Alpha = 'a';

    case Beta = 'b';
}
