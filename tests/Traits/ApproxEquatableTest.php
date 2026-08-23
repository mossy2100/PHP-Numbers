<?php

declare(strict_types=1);

namespace OceanMoon\Core\Tests\Traits;

use OceanMoon\Core\Tests\Fixtures\ApproxEquatableDummy;
use OceanMoon\Core\Traits\Comparison\ApproxEquatable;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

/**
 * Test class for the ApproxEquatable trait.
 */
#[CoversTrait(ApproxEquatable::class)]
final class ApproxEquatableTest extends TestCase
{
    #region Method equal() tests.

    /**
     * Test equal() uses exact equality, unaffected by tolerance.
     */
    public function testEqualUsesExactEquality(): void
    {
        $a = new ApproxEquatableDummy(1.0);
        $b = new ApproxEquatableDummy(1.0 + PHP_FLOAT_EPSILON);

        $this->assertFalse($a->equal($b));
        $this->assertTrue($a->equal(new ApproxEquatableDummy(1.0)));
    }

    #endregion

    #region Method approxEqual() tests.

    /**
     * Test approxEqual() returns true for identical values.
     */
    public function testApproxEqualReturnsTrueForIdenticalValues(): void
    {
        $a = new ApproxEquatableDummy(1.0);
        $b = new ApproxEquatableDummy(1.0);

        $this->assertTrue($a->approxEqual($b));
    }

    /**
     * Test approxEqual() returns true for values within the default tolerance.
     */
    public function testApproxEqualReturnsTrueWithinDefaultTolerance(): void
    {
        $a = new ApproxEquatableDummy(1.0);
        $b = new ApproxEquatableDummy(1.0 + PHP_FLOAT_EPSILON);

        $this->assertTrue($a->approxEqual($b));
    }

    /**
     * Test approxEqual() returns false for values outside the default tolerance.
     */
    public function testApproxEqualReturnsFalseOutsideDefaultTolerance(): void
    {
        $a = new ApproxEquatableDummy(1.0);
        $b = new ApproxEquatableDummy(2.0);

        $this->assertFalse($a->approxEqual($b));
    }

    /**
     * Test approxEqual() respects a custom absolute tolerance.
     */
    public function testApproxEqualWithCustomAbsoluteTolerance(): void
    {
        $a = new ApproxEquatableDummy(0.0);
        $b = new ApproxEquatableDummy(0.5);

        $this->assertFalse($a->approxEqual($b, absTol: 0.1));
        $this->assertTrue($a->approxEqual($b, absTol: 1.0));
    }

    /**
     * Test approxEqual() respects a custom relative tolerance.
     */
    public function testApproxEqualWithCustomRelativeTolerance(): void
    {
        $a = new ApproxEquatableDummy(1000.0);
        $b = new ApproxEquatableDummy(1001.0);

        $this->assertFalse($a->approxEqual($b, relTol: 0.0001, absTol: 0.0));
        $this->assertTrue($a->approxEqual($b, relTol: 0.01, absTol: 0.0));
    }

    #endregion
}
