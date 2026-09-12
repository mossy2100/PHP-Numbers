<?php

declare(strict_types=1);

namespace OceanMoon\Core\Tests;

use DateTime;
use DomainException;
use OceanMoon\Core\Tests\Fixtures\ChildClassUsingTrait;
use OceanMoon\Core\Tests\Fixtures\ClassNotUsingTrait;
use OceanMoon\Core\Tests\Fixtures\ClassUsingNestedTrait;
use OceanMoon\Core\Tests\Fixtures\ClassUsingTrait;
use OceanMoon\Core\Tests\Fixtures\NestedTrait;
use OceanMoon\Core\Tests\Fixtures\TestColor;
use OceanMoon\Core\Tests\Fixtures\TestSuit;
use OceanMoon\Core\Tests\Fixtures\TestTrait;
use OceanMoon\Core\Types;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Test class for Types utility class.
 */
#[CoversClass(Types::class)]
final class TypesTest extends TestCase
{
    #region Method getBasicType() tests.

    /**
     * Test getBasicType with null.
     */
    public function testGetBasicTypeNull(): void
    {
        // Test that null returns 'null'.
        $this->assertSame('null', Types::getBasicType(null));
    }

    /**
     * Test getBasicType with boolean values.
     */
    public function testGetBasicTypeBool(): void
    {
        // Test that booleans return 'bool'.
        $this->assertSame('bool', Types::getBasicType(true));
        $this->assertSame('bool', Types::getBasicType(false));
    }

    /**
     * Test getBasicType with integers.
     */
    public function testGetBasicTypeInt(): void
    {
        // Test that integers return 'int'.
        $this->assertSame('int', Types::getBasicType(0));
        $this->assertSame('int', Types::getBasicType(42));
        $this->assertSame('int', Types::getBasicType(-17));
    }

    /**
     * Test getBasicType with floats.
     */
    public function testGetBasicTypeFloat(): void
    {
        // Test that floats return 'float'.
        $this->assertSame('float', Types::getBasicType(0.0));
        $this->assertSame('float', Types::getBasicType(3.14));
        $this->assertSame('float', Types::getBasicType(-2.5));

        // Test special float values.
        $this->assertSame('float', Types::getBasicType(INF));
        $this->assertSame('float', Types::getBasicType(-INF));
        $this->assertSame('float', Types::getBasicType(NAN));
    }

    /**
     * Test getBasicType with strings.
     */
    public function testGetBasicTypeString(): void
    {
        // Test that strings return 'string'.
        $this->assertSame('string', Types::getBasicType(''));
        $this->assertSame('string', Types::getBasicType('hello'));
        $this->assertSame('string', Types::getBasicType('42'));
    }

    /**
     * Test getBasicType with arrays.
     */
    public function testGetBasicTypeArray(): void
    {
        // Test that arrays return 'array'.
        $this->assertSame('array', Types::getBasicType([]));
        $this->assertSame('array', Types::getBasicType([1, 2, 3]));
        $this->assertSame('array', Types::getBasicType([
            'key' => 'value',
        ]));
    }

    /**
     * Test getBasicType with enums.
     */
    public function testGetBasicTypeEnum(): void
    {
        // Test with a unit enum.
        $this->assertSame('enum', Types::getBasicType(TestSuit::Hearts));

        // Test with a backed enum.
        $this->assertSame('enum', Types::getBasicType(TestColor::Red));
    }

    /**
     * Test getBasicType with closures.
     */
    public function testGetBasicTypeClosure(): void
    {
        // Test that closures return 'closure'.
        $this->assertSame('closure', Types::getBasicType(static fn (int $x): int => $x * 2));
        $this->assertSame('closure', Types::getBasicType(static function (): void {
        }));
        $this->assertSame('closure', Types::getBasicType(strlen(...)));
    }

    /**
     * Test getBasicType with objects.
     */
    public function testGetBasicTypeObject(): void
    {
        // Test that objects return 'object'.
        $this->assertSame('object', Types::getBasicType(new stdClass()));
        $this->assertSame('object', Types::getBasicType(new DateTime()));
        $this->assertSame('object', Types::getBasicType(new class {
        }));
    }

    /**
     * Test getBasicType with resources.
     */
    public function testGetBasicTypeResource(): void
    {
        // Test that resources return 'resource'.
        $resource = fopen('php://memory', 'rb');
        $this->assertIsResource($resource);
        $this->assertSame('resource', Types::getBasicType($resource));
        fclose($resource);
    }

    #endregion

    #region Method getUniqueString() tests.

    /**
     * Test getUniqueString with null.
     */
    public function testGetUniqueStringNull(): void
    {
        // Test that null produces unique key.
        $this->assertSame('n', Types::getUniqueString(null));
    }

    /**
     * Test getUniqueString with boolean values.
     */
    public function testGetUniqueStringBool(): void
    {
        // Test that booleans produce unique keys.
        $this->assertSame('b:T', Types::getUniqueString(true));
        $this->assertSame('b:F', Types::getUniqueString(false));

        // Test that true and false produce different keys.
        $this->assertNotSame(Types::getUniqueString(true), Types::getUniqueString(false));
    }

    /**
     * Test getUniqueString with integers.
     */
    public function testGetUniqueStringInt(): void
    {
        // Test that integers produce unique keys.
        $this->assertSame('i:0', Types::getUniqueString(0));
        $this->assertSame('i:42', Types::getUniqueString(42));
        $this->assertSame('i:-17', Types::getUniqueString(-17));

        // Test that different integers produce different keys.
        $this->assertNotSame(Types::getUniqueString(1), Types::getUniqueString(2));
    }

    /**
     * Test getUniqueString with floats.
     */
    public function testGetUniqueStringFloat(): void
    {
        // Test that floats produce unique keys starting with 'f:'.
        $key1 = Types::getUniqueString(3.14);
        $this->assertStringStartsWith('f:', $key1);

        // Test that different floats produce different keys.
        $key2 = Types::getUniqueString(2.71);
        $this->assertNotSame($key1, $key2);

        // Test that positive and negative zero produce different keys.
        $keyPosZero = Types::getUniqueString(0.0);
        $keyNegZero = Types::getUniqueString(-0.0);
        $this->assertNotSame($keyPosZero, $keyNegZero);

        // Test special float values produce unique keys.
        $this->assertStringStartsWith('f:', Types::getUniqueString(INF));
        $this->assertStringStartsWith('f:', Types::getUniqueString(-INF));
        $this->assertStringStartsWith('f:', Types::getUniqueString(NAN));
    }

    /**
     * Test getUniqueString with strings.
     */
    public function testGetUniqueStringString(): void
    {
        // Test that strings produce keys with format 's:length:content'.
        $this->assertSame('s:5:hello', Types::getUniqueString('hello'));
        $this->assertSame('s:0:', Types::getUniqueString(''));
        $this->assertSame('s:2:42', Types::getUniqueString('42'));

        // Test that different strings produce different keys.
        $this->assertNotSame(Types::getUniqueString('hello'), Types::getUniqueString('world'));

        // Test that strings with same length but different content produce different keys.
        $this->assertNotSame(Types::getUniqueString('abc'), Types::getUniqueString('def'));
    }

    /**
     * Test getUniqueString with arrays.
     */
    public function testGetUniqueStringArray(): void
    {
        // Test that arrays produce keys starting with 'a:count:'.
        $key1 = Types::getUniqueString([1, 2, 3]);
        $this->assertStringStartsWith('a:3:', $key1);

        // Test empty array.
        $key2 = Types::getUniqueString([]);
        $this->assertStringStartsWith('a:0:', $key2);

        // Test that different arrays produce different keys.
        $this->assertNotSame(Types::getUniqueString([1, 2]), Types::getUniqueString([3, 4]));

        // Test associative array.
        $key3 = Types::getUniqueString([
            'key' => 'value',
        ]);
        $this->assertStringStartsWith('a:1:', $key3);
    }

    /**
     * Test getUniqueString with enums.
     */
    public function testGetUniqueStringEnum(): void
    {
        // Test that unit enums produce keys with class and case name.
        $key = Types::getUniqueString(TestSuit::Hearts);
        $this->assertSame('e:OceanMoon\Core\Tests\Fixtures\TestSuit::Hearts', $key);

        // Test that different cases produce different keys.
        $key2 = Types::getUniqueString(TestSuit::Diamonds);
        $this->assertNotSame($key, $key2);

        // Test that same case produces same key.
        $key3 = Types::getUniqueString(TestSuit::Hearts);
        $this->assertSame($key, $key3);

        // Test with a backed enum.
        $key4 = Types::getUniqueString(TestColor::Red);
        $this->assertSame('e:OceanMoon\Core\Tests\Fixtures\TestColor::Red', $key4);

        // Test that enums from different classes are distinct.
        $this->assertNotSame($key, $key4);
    }

    /**
     * Test getUniqueString with closures.
     */
    public function testGetUniqueStringClosure(): void
    {
        // Test that closures produce keys with their object ID.
        $closure1 = static fn (int $x): int => $x + 1;
        $key1 = Types::getUniqueString($closure1);
        $this->assertStringStartsWith('c:', $key1);

        // Test that structurally-identical but distinct closures produce different keys (identity, not value).
        $closure2 = static fn (int $x): int => $x + 1;
        $key2 = Types::getUniqueString($closure2);
        $this->assertNotSame($key1, $key2);

        // Test that the same closure instance produces the same key.
        $closure3 = $closure1;
        $key3 = Types::getUniqueString($closure3);
        $this->assertSame($key1, $key3);

        // Test that a closure's key never collides with a plain object's, despite sharing the same underlying
        // spl_object_id() space.
        $this->assertNotSame($key1, Types::getUniqueString(new stdClass()));
    }

    /**
     * Test getUniqueString with objects.
     */
    public function testGetUniqueStringObject(): void
    {
        // Test that objects produce keys with their object ID.
        $obj1 = new stdClass();
        $key1 = Types::getUniqueString($obj1);
        $this->assertStringStartsWith('o:', $key1);

        // Test that different objects produce different keys.
        $obj2 = new stdClass();
        $key2 = Types::getUniqueString($obj2);
        $this->assertNotSame($key1, $key2);

        // Test that same object produces same key.
        $key3 = Types::getUniqueString($obj1);
        $this->assertSame($key1, $key3);
    }

    /**
     * Test getUniqueString with resources.
     */
    public function testGetUniqueStringResource(): void
    {
        // Test that resources produce keys with their resource ID.
        $resource = fopen('php://memory', 'rb');
        $this->assertIsResource($resource);
        $key = Types::getUniqueString($resource);
        $this->assertStringStartsWith('r:', $key);
        fclose($resource);

        // Test that different resources produce different keys.
        $resource1 = fopen('php://memory', 'rb');
        $this->assertIsResource($resource1);
        $resource2 = fopen('php://memory', 'rb');
        $this->assertIsResource($resource2);
        $this->assertNotSame(Types::getUniqueString($resource1), Types::getUniqueString($resource2));
        fclose($resource1);
        fclose($resource2);
    }

    /**
     * Test getUniqueString produces unique keys for different types.
     */
    public function testGetUniqueStringUniqueness(): void
    {
        // Test that different types produce different keys.
        $keys = [
            Types::getUniqueString(null),
            Types::getUniqueString(true),
            Types::getUniqueString(0),
            Types::getUniqueString(0.0),
            Types::getUniqueString(''),
            Types::getUniqueString([]),
            Types::getUniqueString(static fn (): null => null),
            Types::getUniqueString(new stdClass()),
        ];

        // Verify all keys are unique.
        $this->assertCount(count($keys), array_unique($keys));
    }

    #endregion

    #region Method same() tests.

    /**
     * Test same with identical primitive types.
     */
    public function testSameWithIdenticalPrimitives(): void
    {
        $this->assertTrue(Types::same(1, 2));
        $this->assertTrue(Types::same(1.0, 2.5));
        $this->assertTrue(Types::same('hello', 'world'));
        $this->assertTrue(Types::same(true, false));
        $this->assertTrue(Types::same(null, null));
    }

    /**
     * Test same with different primitive types.
     */
    public function testSameWithDifferentPrimitives(): void
    {
        $this->assertFalse(Types::same(1, 1.0));
        $this->assertFalse(Types::same(1, '1'));
        $this->assertFalse(Types::same(1, true));
        $this->assertFalse(Types::same(0, null));
        $this->assertFalse(Types::same('', false));
        $this->assertFalse(Types::same(1.0, '1.0'));
    }

    /**
     * Test same with arrays.
     */
    public function testSameWithArrays(): void
    {
        $this->assertTrue(Types::same([], [1, 2, 3]));
        $this->assertTrue(Types::same([
            'a' => 1,
        ], [
            'b' => 2,
        ]));
        $this->assertFalse(Types::same([], new stdClass()));
    }

    /**
     * Test same with same object class.
     */
    public function testSameWithSameObjectClass(): void
    {
        $obj1 = new stdClass();
        $obj2 = new stdClass();
        $this->assertTrue(Types::same($obj1, $obj2));

        $dt1 = new DateTime();
        $dt2 = new DateTime();
        $this->assertTrue(Types::same($dt1, $dt2));
    }

    /**
     * Test same with different object classes.
     */
    public function testSameWithDifferentObjectClasses(): void
    {
        $obj1 = new stdClass();
        $obj2 = new DateTime();
        $this->assertFalse(Types::same($obj1, $obj2));
    }

    /**
     * Test same with anonymous classes.
     * Note: get_debug_type() returns 'class@anonymous' for all anonymous classes.
     */
    public function testSameWithAnonymousClasses(): void
    {
        $obj1 = new class {
        };
        $obj2 = new class {
        };
        // All anonymous classes have the same type name according to get_debug_type()
        $this->assertTrue(Types::same($obj1, $obj2));
    }

    /**
     * Test same with resources.
     */
    public function testSameWithResources(): void
    {
        $r1 = fopen('php://memory', 'rb');
        $r2 = fopen('php://memory', 'rb');
        $this->assertNotFalse($r1);
        $this->assertNotFalse($r2);
        $this->assertTrue(Types::same($r1, $r2));
        fclose($r1);
        fclose($r2);
    }

    /**
     * Test same with resource and non-resource.
     */
    public function testSameWithResourceAndNonResource(): void
    {
        $r = fopen('php://memory', 'rb');
        $this->assertNotFalse($r);
        $this->assertFalse(Types::same($r, 'not a resource'));
        $this->assertFalse(Types::same($r, new stdClass()));
        fclose($r);
    }

    /**
     * Test same with special float values.
     */
    public function testSameWithSpecialFloats(): void
    {
        $this->assertTrue(Types::same(INF, -INF));
        $this->assertTrue(Types::same(NAN, 1.0));
        $this->assertTrue(Types::same(0.0, -0.0));
        $this->assertFalse(Types::same(INF, 1));
    }

    /**
     * Test same with parent and child classes.
     */
    public function testSameWithInheritance(): void
    {
        $parent = new ClassNotUsingTrait();
        $child = new ChildClassUsingTrait();
        $this->assertFalse(Types::same($parent, $child));
    }

    /**
     * Test same is symmetric.
     */
    public function testSameSymmetry(): void
    {
        $this->assertSame(
            Types::same(1, 'hello'),
            Types::same('hello', 1)
        );

        $obj1 = new stdClass();
        $obj2 = new DateTime();
        $this->assertSame(
            Types::same($obj1, $obj2),
            Types::same($obj2, $obj1)
        );
    }

    #endregion

    #region Method usesTrait() tests.

    /**
     * Test usesTrait with object using a trait.
     */
    public function testUsesTraitWithObject(): void
    {
        // Create a test trait and class.
        $obj = new class {
            use TestTrait;
        };

        // Test that the method correctly detects trait usage.
        $this->assertTrue(Types::usesTrait($obj, TestTrait::class));

        // Test that it returns false for non-existent trait.
        $this->assertFalse(Types::usesTrait($obj, 'NonExistentTrait'));
    }

    /**
     * Test usesTrait with class name string.
     */
    public function testUsesTraitWithClassName(): void
    {
        // Test with class name string.
        $this->assertTrue(Types::usesTrait(ClassUsingTrait::class, TestTrait::class));
        $this->assertFalse(Types::usesTrait(ClassNotUsingTrait::class, TestTrait::class));
    }

    /**
     * Test usesTrait with inherited traits.
     */
    public function testUsesTraitWithInheritance(): void
    {
        // Test that trait usage is detected through parent class.
        $obj = new ChildClassUsingTrait();
        $this->assertTrue(Types::usesTrait($obj, TestTrait::class));
    }

    /**
     * Test usesTrait with traits that use other traits.
     */
    public function testUsesTraitWithNestedTraits(): void
    {
        // Test that nested trait usage is detected.
        $obj = new ClassUsingNestedTrait();
        $this->assertTrue(Types::usesTrait($obj, TestTrait::class));
        $this->assertTrue(Types::usesTrait($obj, NestedTrait::class));
    }

    /**
     * Test usesTrait with object not using any traits.
     */
    public function testUsesTraitWithNoTraits(): void
    {
        // Test with standard class that doesn't use traits.
        $obj = new stdClass();
        $this->assertFalse(Types::usesTrait($obj, TestTrait::class));
    }

    /**
     * Test usesTrait throws DomainException for non-existent class.
     */
    public function testUsesTraitThrowsExceptionForNonExistentClass(): void
    {
        // Test that passing a non-existent class name throws DomainException.
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("Invalid class name: 'NonExistentClass'. Must be a class, interface, or trait.");
        Types::usesTrait('NonExistentClass', TestTrait::class);
    }

    #endregion

    #region Method getTraits() tests.

    /**
     * Test getTraits with a class using a trait.
     */
    public function testGetTraitsWithClass(): void
    {
        $traits = Types::getTraits(ClassUsingTrait::class);
        $this->assertContains(TestTrait::class, $traits);
        $this->assertCount(1, $traits);
    }

    /**
     * Test getTraits with an object using a trait.
     */
    public function testGetTraitsWithObject(): void
    {
        $obj = new ClassUsingTrait();
        $traits = Types::getTraits($obj);
        $this->assertContains(TestTrait::class, $traits);
    }

    /**
     * Test getTraits with inherited traits.
     */
    public function testGetTraitsWithInheritedTraits(): void
    {
        $traits = Types::getTraits(ChildClassUsingTrait::class);
        $this->assertContains(TestTrait::class, $traits);
    }

    /**
     * Test getTraits with nested traits.
     */
    public function testGetTraitsWithNestedTraits(): void
    {
        $traits = Types::getTraits(ClassUsingNestedTrait::class);
        $this->assertContains(TestTrait::class, $traits);
        $this->assertContains(NestedTrait::class, $traits);
        $this->assertCount(2, $traits);
    }

    /**
     * Test getTraits with a class not using any traits.
     */
    public function testGetTraitsWithNoTraits(): void
    {
        $traits = Types::getTraits(ClassNotUsingTrait::class);
        $this->assertEmpty($traits);
    }

    /**
     * Test getTraits with stdClass (no traits).
     */
    public function testGetTraitsWithStdClass(): void
    {
        $traits = Types::getTraits(stdClass::class);
        $this->assertEmpty($traits);
    }

    /**
     * Test getTraits throws DomainException for non-existent class.
     */
    public function testGetTraitsThrowsExceptionForNonExistentClass(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            "Invalid class name: 'NonExistentClassName'. Must be a class, interface, or trait."
        );
        Types::getTraits('NonExistentClassName');
    }

    #endregion
}
