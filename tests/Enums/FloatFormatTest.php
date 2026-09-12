<?php

declare(strict_types=1);

namespace OceanMoon\Core\Tests\Enums;

use OceanMoon\Core\Enums\FloatFormat;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Test class for the FloatFormat enum.
 */
#[CoversClass(FloatFormat::class)]
final class FloatFormatTest extends TestCase
{
    #region Cases tests.

    /**
     * Test that FloatFormat has exactly the expected cases. FloatFormat is a pure marker enum with no behavior of
     * its own (Floats::format() is where each case's meaning is implemented), so this just guards against a case
     * being silently added, removed, or renamed.
     */
    public function testCases(): void
    {
        $this->assertSame(
            ['FixedPoint', 'Scientific', 'Auto'],
            array_map(static fn (FloatFormat $case) => $case->name, FloatFormat::cases())
        );
    }

    #endregion
}
