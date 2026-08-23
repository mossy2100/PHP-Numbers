<?php

declare(strict_types=1);

namespace OceanMoon\Core\Tests\Enums;

use OceanMoon\Core\Enums\ExponentFormat;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Test class for the ExponentFormat enum.
 */
#[CoversClass(ExponentFormat::class)]
final class ExponentFormatTest extends TestCase
{
    #region Method format() tests.

    /**
     * Test format() with AsciiLowerCaseE: always signed, no padding.
     */
    public function testFormatAsciiLowerCaseE(): void
    {
        $this->assertSame('e+42', ExponentFormat::AsciiLowerCaseE->format(42));
        $this->assertSame('e-42', ExponentFormat::AsciiLowerCaseE->format(-42));
        $this->assertSame('e+0', ExponentFormat::AsciiLowerCaseE->format(0));
    }

    /**
     * Test format() with AsciiUpperCaseE: always signed, no padding.
     */
    public function testFormatAsciiUpperCaseE(): void
    {
        $this->assertSame('E+42', ExponentFormat::AsciiUpperCaseE->format(42));
        $this->assertSame('E-42', ExponentFormat::AsciiUpperCaseE->format(-42));
        $this->assertSame('E+0', ExponentFormat::AsciiUpperCaseE->format(0));
    }

    /**
     * Test format() with AsciiMath: unsigned for positive/zero, minus only for negative.
     */
    public function testFormatAsciiMath(): void
    {
        $this->assertSame('*10^42', ExponentFormat::AsciiMath->format(42));
        $this->assertSame('*10^-42', ExponentFormat::AsciiMath->format(-42));
        $this->assertSame('*10^0', ExponentFormat::AsciiMath->format(0));
    }

    /**
     * Test format() with UnicodeMath: unsigned for positive/zero, minus only for negative, superscript digits.
     */
    public function testFormatUnicodeMath(): void
    {
        $this->assertSame('×10⁴²', ExponentFormat::UnicodeMath->format(42));
        $this->assertSame('×10⁻⁴²', ExponentFormat::UnicodeMath->format(-42));
        $this->assertSame('×10⁰', ExponentFormat::UnicodeMath->format(0));
    }

    /**
     * Test format() with HtmlMath: unsigned for positive/zero, minus only for negative.
     */
    public function testFormatHtmlMath(): void
    {
        $this->assertSame('&times;10<sup>42</sup>', ExponentFormat::HtmlMath->format(42));
        $this->assertSame('&times;10<sup>-42</sup>', ExponentFormat::HtmlMath->format(-42));
        $this->assertSame('&times;10<sup>0</sup>', ExponentFormat::HtmlMath->format(0));
    }

    #endregion
}
