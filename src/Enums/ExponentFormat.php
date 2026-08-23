<?php

declare(strict_types=1);

namespace OceanMoon\Core\Enums;

use OceanMoon\Core\Integers;

/**
 * Rendering styles for the exponent portion of scientific-notation float output.
 */
enum ExponentFormat
{
    /**
     * ASCII lower-case E notation (e.g. 'e+42'). The exponent is not padded to a fixed width.
     */
    case AsciiLowerCaseE;

    /**
     * ASCII upper-case E notation (e.g. 'E+42'). The exponent is not padded to a fixed width. Matches the
     * scientific notation PHP itself falls back to when casting a float to string (e.g. (string) 1.5e20 ===
     * '1.5E+20'), including the explicit sign.
     */
    case AsciiUpperCaseE;

    /**
     * ASCII mathematical notation (e.g. '*10^42'). Uses ASCII operators and digits.
     */
    case AsciiMath;

    /**
     * Unicode mathematical notation (e.g. '×10⁴²'). Uses the multiplication sign and superscript digits.
     */
    case UnicodeMath;

    /**
     * HTML mathematical notation (e.g. '&times;10<sup>42</sup>').
     */
    case HtmlMath;

    /**
     * Render a signed exponent as a string, ready to append directly after a (possibly trimmed) mantissa.
     *
     * AsciiLowerCaseE and AsciiUpperCaseE always include an explicit sign ('+' or '-'), matching sprintf()'s own
     * 'e'/'E' conventions (AsciiUpperCaseE also matches PHP's own float-to-string cast, which always signs the
     * exponent). The three mathematical notations (AsciiMath, UnicodeMath, HtmlMath) omit the '+' for positive
     * exponents and show only '-' for negative ones, matching how scientific notation is conventionally written
     * by hand (e.g. '×10³', not '×10+3').
     *
     * @param int $exponent The exponent (may be negative, zero, or positive).
     * @return string The rendered exponent, e.g. 'e+42', 'E-42', '*10^42', '×10⁴²', '&times;10<sup>42</sup>'.
     */
    public function format(int $exponent): string
    {
        return match ($this) {
            self::AsciiLowerCaseE => sprintf('e%+d', $exponent),
            self::AsciiUpperCaseE => sprintf('E%+d', $exponent),
            self::AsciiMath => '*10^' . $exponent,
            self::UnicodeMath => '×10' . Integers::toSuperscript($exponent),
            self::HtmlMath => '&times;10<sup>' . $exponent . '</sup>',
        };
    }
}
