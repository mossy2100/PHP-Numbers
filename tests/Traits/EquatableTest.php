<?php

declare(strict_types=1);

namespace OceanMoon\Core\Tests\Traits;

use InvalidArgumentException;
use OceanMoon\Core\Tests\Fixtures\EquatableDummy;
use OceanMoon\Core\Traits\Comparison\Equatable;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

/**
 * Test class for the Equatable trait.
 */
#[CoversTrait(Equatable::class)]
final class EquatableTest extends TestCase
{
    #region Method equal() tests.

    /**
     * Test equal() returns true for two instances with the same value.
     */
    public function testEqualReturnsTrueForSameValue(): void
    {
        $a = new EquatableDummy(5);
        $b = new EquatableDummy(5);

        $this->assertTrue($a->equal($b));
    }

    /**
     * Test equal() returns false for two instances with different values.
     */
    public function testEqualReturnsFalseForDifferentValue(): void
    {
        $a = new EquatableDummy(5);
        $b = new EquatableDummy(6);

        $this->assertFalse($a->equal($b));
    }

    /**
     * Test equal() is reflexive: an instance is always equal to itself.
     */
    public function testEqualIsReflexive(): void
    {
        $a = new EquatableDummy(5);

        $this->assertTrue($a->equal($a));
    }

    /**
     * Test equal() throws for a value of a type that cannot meaningfully be compared.
     */
    public function testEqualThrowsForIncompatibleType(): void
    {
        $a = new EquatableDummy(5);

        $this->expectException(InvalidArgumentException::class);
        $a->equal('not a dummy');
    }

    #endregion
}
