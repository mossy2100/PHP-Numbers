<?php

declare(strict_types=1);

namespace OceanMoon\Core\Tests\Stringify;

use Closure;
use DomainException;
use InvalidArgumentException;
use OceanMoon\Core\Stringify;
use OceanMoon\Core\Tests\Fixtures\TestBackedEnum;
use OceanMoon\Core\Tests\Fixtures\TestEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use const OceanMoon\Core\RECURSION;

/**
 * Test class for Stringify utility class - type-specific stringification methods.
 */
#[CoversClass(Stringify::class)]
final class StringifyTypeSpecificTest extends TestCase
{
    #region Method stringifyBool() tests.

    /**
     * Test stringifying boolean values.
     */
    public function testStringifyBool(): void
    {
        $this->assertSame('true', Stringify::stringify(true));
        $this->assertSame('false', Stringify::stringify(false));
    }

    #endregion

    #region Method stringifyInt() tests.

    /**
     * Test stringifying integer values.
     */
    public function testStringifyInt(): void
    {
        $this->assertSame('0', Stringify::stringify(0));
        $this->assertSame('42', Stringify::stringify(42));
        $this->assertSame('-17', Stringify::stringify(-17));
        $this->assertSame('1000000', Stringify::stringify(1000000));
    }

    #endregion

    #region Method stringifyFloat() tests.

    /**
     * Test stringifying float values.
     */
    public function testStringifyFloat(): void
    {
        $this->assertSame('3.14', Stringify::stringifyFloat(3.14));
        $this->assertSame('-2.5', Stringify::stringifyFloat(-2.5));

        // Float that looks like integer gets .0 appended.
        $this->assertSame('5.0', Stringify::stringifyFloat(5.0));
        $this->assertSame('-10.0', Stringify::stringifyFloat(-10.0));
        $this->assertSame('0.0', Stringify::stringifyFloat(0.0));

        // Float with exponent notation (already distinguishable from int).
        $result = Stringify::stringifyFloat(1.5e100);
        $this->assertMatchesRegularExpression('/[eE]/', $result);

        // Very small float with exponent notation.
        $result = Stringify::stringifyFloat(1.5e-10);
        $this->assertMatchesRegularExpression('/[eE]/', $result);
    }

    /**
     * Test stringifying special float values.
     */
    public function testStringifyFloatSpecial(): void
    {
        $this->assertSame('NAN', Stringify::stringifyFloat(NAN));
        $this->assertSame('INF', Stringify::stringifyFloat(INF));
        $this->assertSame('-INF', Stringify::stringifyFloat(-INF));
        $this->assertSame('-0.0', Stringify::stringifyFloat(-0.0));
    }

    /**
     * Test that stringify() correctly dispatches floats.
     */
    public function testStringifyFloatIntegration(): void
    {
        $this->assertSame('5.0', Stringify::stringify(5.0));
        $this->assertSame('3.14', Stringify::stringify(3.14));
        $this->assertSame('NAN', Stringify::stringify(NAN));
        $this->assertSame('INF', Stringify::stringify(INF));
    }

    #endregion

    #region Method stringifyString() tests.

    /**
     * Test stringifying string values via stringify() dispatch.
     */
    public function testStringifyString(): void
    {
        $this->assertSame("'hello'", Stringify::stringify('hello'));
        $this->assertSame("''", Stringify::stringify(''));
        $this->assertSame("'hello\nworld'", Stringify::stringify("hello\nworld"));
        $this->assertSame("'hello\tworld'", Stringify::stringify("hello\tworld"));
        $this->assertSame("'say \"hello\"'", Stringify::stringify('say "hello"'));
    }

    /**
     * Test stringifyString() directly, including escaping of backslashes and single quotes.
     */
    public function testStringifyStringDirect(): void
    {
        // Basic string.
        $this->assertSame("'hello'", Stringify::stringifyString('hello'));

        // Single quotes are escaped; double quotes are not.
        $this->assertSame("'it\\'s'", Stringify::stringifyString("it's"));

        // Backslashes are escaped.
        $this->assertSame("'foo\\\\bar'", Stringify::stringifyString("foo\\bar"));

        // Backslash immediately before a double quote.
        $this->assertSame("'it\\\\\"s'", Stringify::stringifyString('it\\"s'));
    }

    /**
     * Test stringifyString() converts non-UTF-8 input to UTF-8 when encoding is detectable.
     */
    public function testStringifyStringNonUtf8Conversion(): void
    {
        // Add ISO-8859-1 to the detect order so mb_detect_encoding can find it.
        $originalOrder = mb_detect_order();
        if (empty($originalOrder)) {
            $originalOrder = ['ASCII', 'UTF-8', 'ISO-8859-1'];
        }
        mb_detect_order(['ASCII', 'UTF-8', 'ISO-8859-1']);

        try {
            // 'café' encoded as Latin-1 (0xe9 is é in ISO-8859-1).
            $latin1 = "caf\xe9";
            $this->assertSame("'café'", Stringify::stringifyString($latin1));
        } finally {
            mb_detect_order($originalOrder);
        }
    }

    /**
     * Test stringifyString() throws DomainException when encoding cannot be detected.
     */
    public function testStringifyStringUndetectableEncoding(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('String encoding cannot be detected.');
        Stringify::stringifyString("\xfe\xff");
    }

    /**
     * Test that Unicode characters in strings are preserved, not escaped.
     */
    public function testStringifyStringUnicode(): void
    {
        $this->assertSame("'Ω'", Stringify::stringify('Ω'));
        $this->assertSame("'café'", Stringify::stringify('café'));
        $this->assertSame("'日本語'", Stringify::stringify('日本語'));
    }

    #endregion

    #region Method stringifyArray() tests.

    /**
     * Test stringifying simple lists without pretty print.
     */
    public function testStringifyListArray(): void
    {
        $this->assertSame('[]', Stringify::stringify([]));
        $this->assertSame('[1, 2, 3]', Stringify::stringify([1, 2, 3]));
        $this->assertSame("[1, 'hello', true, null]", Stringify::stringify([1, 'hello', true, null]));
        $this->assertSame('[1.5, 2.0, 3.14]', Stringify::stringify([1.5, 2.0, 3.14]));
    }

    /**
     * Test stringifying dictionaries without pretty print.
     */
    public function testStringifyAssociativeArray(): void
    {
        // Simple dictionary.
        $this->assertSame("['name' => 'John', 'age' => 30]", Stringify::stringify([
            'name' => 'John',
            'age'  => 30,
        ]));

        // Non-sequential integer keys.
        $this->assertSame("[1 => 'a', 3 => 'b', 5 => 'c']", Stringify::stringify([
            1 => 'a',
            3 => 'b',
            5 => 'c',
        ]));

        // Mixed key types.
        $this->assertSame("['key' => 'value', 0 => 42]", Stringify::stringify([
            'key' => 'value',
            0     => 42,
        ]));
    }

    /**
     * Test stringifying nested arrays without pretty print.
     */
    public function testStringifyNestedArray(): void
    {
        // Nested list.
        $this->assertSame('[[1, 2], [3, 4]]', Stringify::stringify([
            [1, 2],
            [3, 4],
        ]));

        // Nested dictionary.
        $this->assertSame("['user' => ['name' => 'John', 'age' => 30]]", Stringify::stringify([
            'user' => [
                'name' => 'John',
                'age'  => 30,
            ],
        ]));

        // Mixed nesting.
        $this->assertSame("[1, ['a', 'b'], 3]", Stringify::stringify([
            1,
            ['a', 'b'],
            3,
        ]));
    }

    /**
     * Test that a short scalar list fits on one line when pretty printing.
     */
    public function testStringifyArrayPrettyPrintShortList(): void
    {
        $this->assertSame('[1, 2, 3]', Stringify::stringify([1, 2, 3], true));
    }

    /**
     * Test that a long scalar list uses grid format when pretty printing.
     */
    public function testStringifyArrayPrettyPrintGrid(): void
    {
        $maxLineLength = Stringify::getMaxLineLength();

        // Create a list long enough to exceed 120 chars and trigger grid format.
        $list = range(1, 50);
        $result = Stringify::stringify($list, true);

        // Should be multiline.
        $this->assertStringContainsString("\n", $result);

        // Should start with [ and end with ].
        $this->assertStringStartsWith('[', $result);
        $this->assertStringEndsWith(']', $result);

        // Each line (except first/last) should be indented and less than max length.
        $lines = explode("\n", $result);
        for ($i = 1; $i < count($lines) - 1; $i++) {
            $this->assertStringStartsWith('    ', $lines[$i]);
            $this->assertLessThanOrEqual($maxLineLength, strlen($lines[$i]));
        }
    }

    /**
     * Test pretty-printed dictionary with aligned keys.
     */
    public function testStringifyArrayPrettyPrintDictionary(): void
    {
        $result = Stringify::stringify([
            'name' => 'John',
            'age'  => 30,
        ], true);

        $expected = "[\n    'name' => 'John',\n    'age'  => 30,\n]";
        $this->assertSame($expected, $result);
    }

    /**
     * Test pretty-printed list of arrays uses one item per line.
     */
    public function testStringifyArrayPrettyPrintListOfArrays(): void
    {
        $result = Stringify::stringify([
            [1, 2],
            [3, 4],
        ], true);

        // Should be single-line.
        $this->assertSame("[\n    [1, 2],\n    [3, 4],\n]", $result);
    }

    /**
     * Test that a list containing an associative array uses one-per-line format.
     */
    public function testStringifyArrayPrettyPrintMultilineItem(): void
    {
        $result = Stringify::stringify([
            [
                'name' => 'John',
                'age'  => 30,
            ],
            42,
        ], true);

        $expected = "[\n"
            . "    [\n"
            . "        'name' => 'John',\n"
            . "        'age'  => 30,\n"
            . "    ],\n"
            . "    42,\n"
            . ']';
        $this->assertSame($expected, $result);
    }

    /**
     * Test that a list with long items falls back to one-per-line format (no grid padding).
     */
    public function testStringifyArrayPrettyPrintLongItems(): void
    {
        $uuids = [
            'c9e35c00-0f1e-4804-b5fe-6c4c9718db60', 'd2aee4c5-a7f7-4018-a635-c3f4c317033e',
            'd266963a-c4e0-4255-a97d-f070e51fcb5e',
        ];

        // maxLineLength of 40 — items are too wide for 2 per line, so grid is skipped.
        Stringify::setMaxLineLength(40);
        try {
            $result = Stringify::stringifyArray($uuids, true);

            $expected = "[\n"
                . "    'c9e35c00-0f1e-4804-b5fe-6c4c9718db60',\n"
                . "    'd2aee4c5-a7f7-4018-a635-c3f4c317033e',\n"
                . "    'd266963a-c4e0-4255-a97d-f070e51fcb5e',\n"
                . ']';
            $this->assertSame($expected, $result);
        } finally {
            Stringify::resetDefaults();
        }
    }

    /**
     * Test that circular references in arrays.
     */
    public function testStringifyArrayCircularReference(): void
    {
        $array = [
            'foo' => 'bar',
        ];
        $array['self'] = &$array;

        $result = Stringify::stringify($array);

        $this->assertStringContainsString(RECURSION, $result);
        $this->assertStringNotContainsString("'" . RECURSION . "'", $result);
    }

    #endregion

    #region Method stringifyResource() tests.

    /**
     * Test stringifying an open resource.
     */
    public function testStringifyResource(): void
    {
        $resource = fopen('php://memory', 'rb');
        $this->assertIsResource($resource);

        $this->assertMatchesRegularExpression('/^resource #\d+ \(stream\)$/', Stringify::stringify($resource));

        fclose($resource);
    }

    /**
     * Test stringifying a closed resource.
     */
    public function testStringifyClosedResource(): void
    {
        $resource = fopen('php://memory', 'rb');
        $this->assertIsResource($resource);
        fclose($resource);

        $this->assertMatchesRegularExpression(
            '/^resource #\d+ \(closed\)$/',
            Stringify::stringifyResource($resource)
        );
    }

    /**
     * Test stringifying resource with non-resource value throws InvalidArgumentException.
     */
    public function testStringifyResourceWithNonResource(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value type: string. Must be a resource.');
        Stringify::stringifyResource('not a resource');
    }

    #endregion

    #region Method stringifyEnum() tests.

    /**
     * Test stringifying enum cases.
     */
    public function testStringifyEnum(): void
    {
        // Test that stringify() dispatches enums correctly.
        $result = Stringify::stringify(TestEnum::Foo);
        $this->assertSame('OceanMoon\Core\Tests\Fixtures\TestEnum::Foo', $result);
    }

    /**
     * Test stringifyEnum() directly.
     */
    public function testStringifyEnumDirect(): void
    {
        $this->assertSame('OceanMoon\Core\Tests\Fixtures\TestEnum::Foo', Stringify::stringifyEnum(TestEnum::Foo));
        $this->assertSame('OceanMoon\Core\Tests\Fixtures\TestEnum::Bar', Stringify::stringifyEnum(TestEnum::Bar));
    }

    /**
     * Test stringifying backed enum cases.
     */
    public function testStringifyBackedEnum(): void
    {
        $this->assertSame(
            'OceanMoon\Core\Tests\Fixtures\TestBackedEnum::Alpha',
            Stringify::stringifyEnum(TestBackedEnum::Alpha)
        );
    }

    #endregion

    #region Method stringifyClosure() tests.

    /**
     * Test that stringify() dispatches closures correctly, reading back the original source.
     */
    public function testStringifyClosure(): void
    {
        $sqr = static fn (float $x): float => $x * $x;
        $this->assertSame('static fn (float $x): float => $x * $x', Stringify::stringify($sqr));
    }

    /**
     * Test stringifyClosure() directly, with a non-static arrow function and no return type.
     */
    public function testStringifyClosureArrow(): void
    {
        // Deliberately non-static; the test asserts stringifyClosure() omits the "static " prefix.
        // phpcs:ignore SlevomatCodingStandard.Functions.StaticClosure
        $add = fn (int $a, int $b) => $a + $b;
        $this->assertSame('fn (int $a, int $b) => $a + $b', Stringify::stringifyClosure($add));
    }

    /**
     * Test stringifyClosure() with an arrow function whose body contains its own nested brackets (a call within a
     * call). This exercises findArrowBodyEnd()'s bracket depth-tracking: the inner brackets must be opened and
     * closed (incrementing and decrementing depth) without being mistaken for the arrow body's own terminator,
     * which only applies at depth 0.
     */
    public function testStringifyClosureArrowWithNestedBrackets(): void
    {
        // phpcs:ignore SlevomatCodingStandard.Functions.StaticClosure
        $closure = fn ($x) => strlen(trim($x));
        $this->assertSame('fn ($x) => strlen(trim($x))', Stringify::stringifyClosure($closure));
    }

    /**
     * Test stringifyClosure() with a brace-style closure prints the code exactly as written, including its
     * original whitespace and indentation.
     */
    public function testStringifyClosureBraceStyle(): void
    {
        // Deliberately brace-style (not arrow) and non-static, to exercise that code path specifically.
        // phpcs:ignore SlevomatCodingStandard.Functions.StaticClosure, SlevomatCodingStandard.Functions.RequireArrowFunction
        $cube = function (float $x): float {
            return $x ** 3;
        };

        $expected = "function (float \$x): float {\n"
            . "            return \$x ** 3;\n"
            . '        }';
        $this->assertSame($expected, Stringify::stringifyClosure($cube));
    }

    /**
     * Test stringifyClosure() with a closure embedded as a non-last argument excludes the trailing comma, since
     * it belongs to the surrounding call, not the closure itself.
     */
    public function testStringifyClosureEmbeddedAsNonLastArgument(): void
    {
        // Deliberately non-static, matching the other embedded-argument closure tests below.
        // phpcs:ignore SlevomatCodingStandard.Functions.StaticClosure
        $closure = self::first(fn ($x) => $x + 1, 'ignored');
        $this->assertSame('fn ($x) => $x + 1', Stringify::stringifyClosure($closure));
    }

    /**
     * Test stringifyClosure() with a closure embedded as a sole/last argument excludes the trailing closing
     * parenthesis, since it belongs to the surrounding call, not the closure itself.
     */
    public function testStringifyClosureEmbeddedAsLastArgument(): void
    {
        // phpcs:ignore SlevomatCodingStandard.Functions.StaticClosure
        $closure = self::identity(fn ($x) => $x + 1);
        $this->assertSame('fn ($x) => $x + 1', Stringify::stringifyClosure($closure));
    }

    /**
     * Test stringifyClosure() with a closure embedded as the last value in an array literal excludes the trailing
     * closing bracket, since it belongs to the surrounding array, not the closure itself.
     */
    public function testStringifyClosureEmbeddedAsArrayValue(): void
    {
        // phpcs:ignore SlevomatCodingStandard.Functions.StaticClosure
        $closure = [
            'ignored',
            static fn ($x) => $x + 1,
        ][1];
        $this->assertSame('static fn ($x) => $x + 1', Stringify::stringifyClosure($closure));
    }

    /**
     * Test stringifyClosure() returns '' for a closure with no available source, e.g. one wrapping an internal
     * function (ReflectionFunction::getFileName() returns false for those).
     */
    public function testStringifyClosureNoSource(): void
    {
        $closure = strlen(...);
        $this->assertSame('', Stringify::stringifyClosure($closure));
    }

    /**
     * Returns its first argument unchanged. Used to capture a closure while it's still embedded inline as a
     * non-last call argument, so stringifyClosure()'s boundary detection can be exercised for that case.
     */
    private static function first(Closure $a, mixed $b): Closure
    {
        return $a;
    }

    /**
     * Returns its argument unchanged. Used to capture a closure while it's still embedded inline as a sole/last
     * call argument, so stringifyClosure()'s boundary detection can be exercised for that case.
     */
    private static function identity(Closure $value): Closure
    {
        return $value;
    }

    #endregion

    #region Method stringifyObject() tests.

    /**
     * Test stringifying simple objects.
     */
    public function testStringifyObject(): void
    {
        $obj = new class {
            public string $name = 'John';

            public int $age = 30;
        };

        $result = Stringify::stringify($obj);
        $this->assertStringContainsString('class@anonymous', $result);
        $this->assertStringContainsString("+name => 'John'", $result);
        $this->assertStringContainsString('+age => 30', $result);
        $this->assertStringEndsWith('}', $result);
    }

    /**
     * Test stringifying objects with different visibility modifiers.
     */
    public function testStringifyObjectVisibility(): void
    {
        $obj = new class {
            public string $publicProp = 'public';

            protected string $protectedProp = 'protected';

            // @phpstan-ignore-next-line
            private string $privateProp = 'private';
        };

        $result = Stringify::stringify($obj);

        $this->assertStringContainsString("+publicProp => 'public'", $result);
        $this->assertStringContainsString("#protectedProp => 'protected'", $result);
        $this->assertStringContainsString("-privateProp => 'private'", $result);
    }

    /**
     * Test stringifying empty objects.
     */
    public function testStringifyEmptyObject(): void
    {
        $obj = new class {
        };

        $result = Stringify::stringify($obj);
        $this->assertMatchesRegularExpression('/^class@anonymous #\d+ {}$/', $result);
    }

    /**
     * Test stringifying objects with pretty print.
     */
    public function testStringifyObjectPrettyPrint(): void
    {
        $obj = new class {
            public string $name = 'John';

            public int $age = 30;
        };

        $result = Stringify::stringify($obj, true);

        $this->assertMatchesRegularExpression(
            '/^class@anonymous #\d+ \{\n\s+\+name\s+=> \'John\',\n\s+\+age\s+=> 30,\n\}$/',
            $result
        );
    }

    /**
     * Test stringifying nested structures with objects and arrays.
     */
    public function testStringifyComplexNesting(): void
    {
        $obj = new class {
            /** @var array<int, int> */
            public array $items = [1, 2, 3];

            public string $name = 'test';
        };

        $array = [
            'object'  => $obj,
            'numbers' => [4, 5, 6],
        ];

        $result = Stringify::stringify($array);
        $this->assertStringContainsString("'object' => class@anonymous", $result);
        $this->assertStringContainsString('+items => [1, 2, 3]', $result);
        $this->assertStringContainsString("+name => 'test'", $result);
        $this->assertStringContainsString("'numbers' => [4, 5, 6]", $result);
    }

    /**
     * Test that an object with a direct self-reference is stringified with the RECURSION marker,
     * instead of recursing forever.
     */
    public function testStringifyObjectDirectSelfReference(): void
    {
        $obj = new class {
            public mixed $self = null;
        };
        $obj->self = $obj;

        $result = Stringify::stringify($obj);

        $this->assertStringContainsString('+self => ' . RECURSION, $result);
    }

    /**
     * Test that mutual (indirect) object-to-object recursion is stringified with the RECURSION
     * marker, instead of recursing forever. Arrays::removeRecursion() alone can't catch this, since
     * it only inspects array values, never object properties.
     */
    public function testStringifyObjectMutualReference(): void
    {
        $a = new class {
            public mixed $other = null;
        };
        $b = new class {
            public mixed $other = null;
        };
        $a->other = $b;
        $b->other = $a;

        $result = Stringify::stringify($a);

        $this->assertStringContainsString(RECURSION, $result);
    }

    /**
     * Test that a cyclic reference reached via a nested array (object -> array -> same object) is
     * still detected.
     */
    public function testStringifyObjectCycleViaArray(): void
    {
        $obj = new class {
            /** @var array<string, mixed> */
            public array $items = [];
        };
        $obj->items = [
            'self' => $obj,
        ];

        $result = Stringify::stringify($obj);

        $this->assertStringContainsString(RECURSION, $result);
    }

    /**
     * Test that the same object referenced by two sibling (non-cyclic) properties is NOT mistaken
     * for recursion — it should render in full both times, since it's a shared reference, not a
     * cycle.
     */
    public function testStringifyObjectSharedReferenceNotRecursive(): void
    {
        $shared = new class {
            public int $value = 42;
        };
        $obj = new class {
            public mixed $a = null;

            public mixed $b = null;
        };
        $obj->a = $shared;
        $obj->b = $shared;

        $result = Stringify::stringify($obj);

        $this->assertStringNotContainsString(RECURSION, $result);
        $this->assertSame(2, substr_count($result, '+value => 42'));
    }

    #endregion
}
