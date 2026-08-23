<?php

/**
 * @file
 * Useful constants that work better as global.
 */

declare(strict_types=1);

namespace OceanMoon\Core;

/**
 * The circle constant tau τ (tau) = 2π. Equal to the number of radians in a circle.
 *
 * To use it without requiring the namespace every time, include the following line:
 * ```php
 * use const OceanMoon\Core\M_TAU;
 * ```
 */
const M_TAU = 2 * M_PI; // @codeCoverageIgnore

/**
 * The marker used by Arrays and Stringify to represent a circular reference.
 *
 * This is intended to match the recursion marker text ("*RECURSION*") used by the print_r() function. It's unlikely to
 * be required outside of debugging, but placed here because it's used by both Arrays and Stringify.
 */
const RECURSION = '*RECURSION*'; // @codeCoverageIgnore
