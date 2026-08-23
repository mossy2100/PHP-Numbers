<?php

declare(strict_types=1);

namespace OceanMoon\Core\Tests\Stringify;

use ArgumentCountError;
use BadMethodCallException;
use DomainException;
use OceanMoon\Core\Stringify;
use OceanMoon\Core\Tests\Fixtures\Foo;
use OceanMoon\Core\Tests\Fixtures\StringableThing;
use OceanMoon\Core\Tests\Fixtures\StringifyAbbrevAnObjectWithAVeryVeryLongClassNameIndeed;
use OceanMoon\Core\Tests\Fixtures\Suit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use const OceanMoon\Core\RECURSION;

/**
 * Test class for Stringify utility class - main stringification methods.
 */
#[CoversClass(Stringify::class)]
final class StringifyMainTest extends TestCase
{
    #region Method stringify() tests.

    /**
     * Test stringifying null values. Unlike every other type, null has no dedicated stringifyX() method - it's
     * handled directly inside stringify()'s own dispatch logic.
     */
    public function testStringifyNull(): void
    {
        $this->assertSame('null', Stringify::stringify(null));
    }

    #endregion

    #region Method toString() tests.

    /**
     * Test Stringify::toString() with a string returns it unchanged.
     */
    public function testToStringWithString(): void
    {
        $this->assertSame('Hello', Stringify::toString('Hello'));
    }

    /**
     * Test Stringify::toString() with an integer.
     */
    public function testToStringWithInt(): void
    {
        $this->assertSame('42', Stringify::toString(42));
        $this->assertSame('-17', Stringify::toString(-17));
    }

    /**
     * Test Stringify::toString() with a float. Uses a raw (string) cast, not Stringify::stringifyFloat(), so
     * a whole-number float loses its distinguishing ".0" suffix.
     */
    public function testToStringWithFloat(): void
    {
        $this->assertSame('3.14', Stringify::toString(3.14));
        $this->assertSame('5', Stringify::toString(5.0));
    }

    /**
     * Test Stringify::toString() with non-finite floats (NAN, INF, -INF). Casting these to string directly emits a
     * "coerced to string" warning (PHP 8.5+), which Stringify::toString() avoids.
     */
    public function testToStringWithNonFiniteFloats(): void
    {
        $this->assertSame('NAN', Stringify::toString(NAN));
        $this->assertSame('INF', Stringify::toString(INF));
        $this->assertSame('-INF', Stringify::toString(-INF));
    }

    /**
     * Test Stringify::toString() with null uses PHP's raw (string) cast, giving an empty string.
     */
    public function testToStringWithNull(): void
    {
        $this->assertSame('', Stringify::toString(null));
    }

    /**
     * Test Stringify::toString() with booleans uses PHP's raw (string) cast: '1' for true, '' for false.
     */
    public function testToStringWithBool(): void
    {
        $this->assertSame('1', Stringify::toString(true));
        $this->assertSame('', Stringify::toString(false));
    }

    /**
     * Test Stringify::toString() with an array falls back to Stringify's concise representation.
     */
    public function testToStringWithArray(): void
    {
        $this->assertSame('[1, 2, 3]', Stringify::toString([1, 2, 3]));
        $this->assertSame("['a' => 1]", Stringify::toString([
            'a' => 1,
        ]));
    }

    /**
     * Test Stringify::toString() with an array containing a circular reference doesn't error.
     */
    public function testToStringWithRecursiveArray(): void
    {
        $arr = [
            'x' => 1,
        ];
        $arr['self'] = &$arr;

        $this->assertSame("['x' => 1, 'self' => " . RECURSION . ']', Stringify::toString($arr));
    }

    /**
     * Test Stringify::toString() with a Stringable object uses __toString() directly.
     */
    public function testToStringWithStringableObject(): void
    {
        $this->assertSame('custom', Stringify::toString(new StringableThing()));
    }

    /**
     * Test Stringify::toString() with an enum case falls back to Stringify.
     */
    public function testToStringWithEnum(): void
    {
        $this->assertSame('OceanMoon\Core\Tests\Fixtures\Suit::Hearts', Stringify::toString(Suit::Hearts));
    }

    /**
     * Test Stringify::toString() with a non-Stringable object falls back to Stringify, showing the class name
     * and properties. The object ID Stringify now includes in its output is non-deterministic, so
     * this checks the shape rather than an exact string.
     */
    public function testToStringWithNonStringableObject(): void
    {
        $result = Stringify::toString(new Foo());

        $this->assertMatchesRegularExpression(
            '/^OceanMoon\\\\Core\\\\Tests\\\\Fixtures\\\\Foo #\d+ \{\+a => 1, #b => 2, -c => 3\}$/',
            $result
        );
    }

    /**
     * Test Stringify::toString() with a resource.
     */
    public function testToStringWithResource(): void
    {
        $resource = fopen('php://memory', 'rb');
        $this->assertIsResource($resource);

        $this->assertMatchesRegularExpression('/^Resource id #\d+$/', Stringify::toString($resource));

        fclose($resource);
    }

    /**
     * Test Stringify::toString() with no argument throws.
     */
    public function testToStringWithNoArgumentThrows(): void
    {
        $this->expectException(ArgumentCountError::class);
        Stringify::toString(); // @phpstan-ignore arguments.count
    }

    #endregion

    #region Method abbrev() tests.

    /**
     * Test abbrev method with short strings.
     */
    public function testAbbrevShortString(): void
    {
        $this->assertSame("'hello'", Stringify::abbrev('hello'));
        $this->assertSame('42', Stringify::abbrev(42));
        $this->assertSame('true', Stringify::abbrev(true));
    }

    /**
     * Test abbrev method with long strings.
     */
    public function testAbbrevLongString(): void
    {
        $longString = 'this is a very long string that should be truncated';
        $result = Stringify::abbrev($longString, 20);

        $this->assertLessThanOrEqual(20, mb_strlen($result));
        $this->assertStringEndsWith("…'", $result);
    }

    /**
     * Test abbrev method with arrays.
     */
    public function testAbbrevArray(): void
    {
        $array = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $result = Stringify::abbrev($array, 20);

        $this->assertLessThanOrEqual(20, mb_strlen($result));
        $this->assertStringEndsWith('…]', $result);
    }

    /**
     * Test abbrev method with maximum length too small.
     */
    public function testAbbrevMaxLenTooSmall(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid maximum string length: 2. Must be at least 3.');
        Stringify::abbrev(123, 2);
    }

    /**
     * Test abbrev with an object whose class name alone is longer than $maxLen: the class name must
     * never be truncated, so the worst case is "ClassName".
     */
    public function testAbbrevObjectNeverTruncatesClassName(): void
    {
        $obj = new StringifyAbbrevAnObjectWithAVeryVeryLongClassNameIndeed();

        $result = Stringify::abbrev($obj, 10);

        $this->assertSame(StringifyAbbrevAnObjectWithAVeryVeryLongClassNameIndeed::class, $result);
    }

    /**
     * Test abbrev with an object where $maxLen comfortably covers the class name: normal truncation
     * (based on $maxLen, not the class-name guard) still applies.
     */
    public function testAbbrevObjectRegularTruncation(): void
    {
        $obj = new class {
            public int $a = 1;

            public int $b = 2;

            public int $c = 3;
        };

        $result = Stringify::abbrev($obj, 30);

        $this->assertLessThanOrEqual(30, mb_strlen($result));
        $this->assertStringEndsWith('…}', $result);
    }

    #endregion

    #region Method prepEx() tests.

    /**
     * Test prepEx() substitutes each '?' placeholder, left to right, with abbrev() of the matching value.
     */
    public function testPrepExSubstitutesPlaceholders(): void
    {
        $this->assertSame(
            'Invalid range: [-5, -10]. Min must not exceed max.',
            Stringify::prepEx('Invalid range: [?, ?]. Min must not exceed max.', -5, -10)
        );
    }

    /**
     * Test prepEx() with no placeholders and no values returns the message unchanged.
     */
    public function testPrepExWithNoPlaceholders(): void
    {
        $this->assertSame('No placeholders here.', Stringify::prepEx('No placeholders here.'));
    }

    /**
     * Test prepEx() inserts a value containing '?' verbatim, without re-scanning it for further placeholders.
     */
    public function testPrepExValueContainingQuestionMark(): void
    {
        $this->assertSame(
            "Value: 'contains a ? mark'",
            Stringify::prepEx('Value: ?', 'contains a ? mark')
        );
    }

    /**
     * Test prepEx() throws when given more values than placeholders.
     */
    public function testPrepExTooManyValuesThrows(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot prepare exception message due to incorrect value count: 2. Expected 1.');
        Stringify::prepEx('One: ?', 1, 2);
    }

    /**
     * Test prepEx() throws when given fewer values than placeholders.
     */
    public function testPrepExTooFewValuesThrows(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot prepare exception message due to incorrect value count: 1. Expected 2.');
        Stringify::prepEx('Two: ? ?', 1);
    }

    #endregion
}
