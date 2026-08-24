<?php

declare(strict_types=1);

namespace OceanMoon\Core\Tests\Traits;

use InvalidArgumentException;
use OceanMoon\Core\Tests\Fixtures\ComparableDummy;
use OceanMoon\Core\Traits\Comparison\Comparable;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

/**
 * Test class for the Comparable trait.
 */
#[CoversTrait(Comparable::class)]
final class ComparableTest extends TestCase
{
    #region Method equal() tests.

    /**
     * Test equal() returns true when compare() returns 0.
     */
    public function testEqualReturnsTrueWhenEqual(): void
    {
        $a = new ComparableDummy(5);
        $b = new ComparableDummy(5);

        $this->assertTrue($a->equal($b));
    }

    /**
     * Test equal() returns false when compare() returns -1 or 1.
     */
    public function testEqualReturnsFalseWhenNotEqual(): void
    {
        $a = new ComparableDummy(5);

        $this->assertFalse($a->equal(new ComparableDummy(4)));
        $this->assertFalse($a->equal(new ComparableDummy(6)));
    }

    /**
     * Test equal() propagates the exception compare() throws for an incompatible type.
     */
    public function testEqualThrowsForIncompatibleType(): void
    {
        $a = new ComparableDummy(5);

        $this->expectException(InvalidArgumentException::class);
        $a->equal('not a dummy');
    }

    #endregion

    #region Method lessThan() tests.

    /**
     * Test lessThan() returns true only when compare() returns -1.
     */
    public function testLessThan(): void
    {
        $a = new ComparableDummy(5);

        $this->assertTrue($a->lessThan(new ComparableDummy(6)));
        $this->assertFalse($a->lessThan(new ComparableDummy(5)));
        $this->assertFalse($a->lessThan(new ComparableDummy(4)));
    }

    #endregion

    #region Method lessThanOrEqual() tests.

    /**
     * Test lessThanOrEqual() returns true unless compare() returns 1.
     */
    public function testLessThanOrEqual(): void
    {
        $a = new ComparableDummy(5);

        $this->assertTrue($a->lessThanOrEqual(new ComparableDummy(6)));
        $this->assertTrue($a->lessThanOrEqual(new ComparableDummy(5)));
        $this->assertFalse($a->lessThanOrEqual(new ComparableDummy(4)));
    }

    #endregion

    #region Method greaterThan() tests.

    /**
     * Test greaterThan() returns true only when compare() returns 1.
     */
    public function testGreaterThan(): void
    {
        $a = new ComparableDummy(5);

        $this->assertTrue($a->greaterThan(new ComparableDummy(4)));
        $this->assertFalse($a->greaterThan(new ComparableDummy(5)));
        $this->assertFalse($a->greaterThan(new ComparableDummy(6)));
    }

    #endregion

    #region Method greaterThanOrEqual() tests.

    /**
     * Test greaterThanOrEqual() returns true unless compare() returns -1.
     */
    public function testGreaterThanOrEqual(): void
    {
        $a = new ComparableDummy(5);

        $this->assertTrue($a->greaterThanOrEqual(new ComparableDummy(4)));
        $this->assertTrue($a->greaterThanOrEqual(new ComparableDummy(5)));
        $this->assertFalse($a->greaterThanOrEqual(new ComparableDummy(6)));
    }

    #endregion
}
