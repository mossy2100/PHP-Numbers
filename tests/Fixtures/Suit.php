<?php

declare(strict_types=1);

namespace OceanMoon\Core\Tests\Fixtures;

/**
 * Test fixture enum, for testing enum handling in to_string().
 */
enum Suit
{
    case Hearts;

    case Spades;
}
