<?php

declare(strict_types=1);

namespace OceanMoon\Core\Enums;

/**
 * Notation styles for Floats::format().
 */
enum FloatFormat
{
    /**
     * Use a decimal point, with no exponential part.
     */
    case FixedPoint;

    /**
     * Include an exponential part.
     */
    case Scientific;

    /**
     * Use whichever of FixedPoint or Scientific produces the more useful string. FixedPoint is preferred unless:
     * 1. Scientific would show more significant figures, or
     * 2. FixedPoint would show more than 3 leading or trailing zeros.
     */
    case Auto;
}
