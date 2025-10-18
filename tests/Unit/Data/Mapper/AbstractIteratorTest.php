<?php

namespace Exodus4D\ESI\Tests\Unit\Data\Mapper;

use Exodus4D\Pathfinder\Data\Mapper\AbstractIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractIterator::class)]
class AbstractIteratorTest extends TestCase
{
    public function testConstructorWithArray(): void
    {
        $data = ['key' => 'value'];
        $iterator = new AbstractIterator($data);

        $this->assertInstanceOf(AbstractIterator::class, $iterator);
    }

    public function testConstructorWithStdClass(): void
    {
        $data = (object)['key' => 'value'];
        $iterator = new AbstractIterator($data);

        $this->assertInstanceOf(AbstractIterator::class, $iterator);
    }

    public function testGetDataReturnsArray(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $iterator = new AbstractIterator($data);

        $result = $iterator->getData();

        $this->assertIsArray($result);
    }

    public function testCamelCaseKeysTransformation(): void
    {
        // Create a concrete implementation to test protected method
        $iterator = new class(['snake_case' => 'value']) extends AbstractIterator {
            protected static $map = [];

            public function testCamelCase(): array {
                return $this->camelCaseKeys(['snake_case' => 'test', 'another_key' => 'value']);
            }
        };

        $result = $iterator->testCamelCase();

        $this->assertArrayHasKey('snakeCase', $result);
        $this->assertArrayHasKey('anotherKey', $result);
    }

    public function testIteratorWithObjectInAssocCheck(): void
    {
        // This test ensures the is_assoc method handles objects
        // by converting them to arrays (line 79)
        $iterator = new class(['nested' => (object)['key' => 'value']]) extends AbstractIterator {
            protected static $map = ['nested' => 'nested'];
            protected static $removeUnmapped = false;
        };

        $result = $iterator->getData();

        $this->assertArrayHasKey('nested', $result);
    }

    public function testRecursiveIteratorWithMapping(): void
    {
        $iterator = new class(['old_key' => 'value']) extends AbstractIterator {
            protected static $map = ['old_key' => 'new_key'];
        };

        $result = $iterator->getData();

        $this->assertArrayHasKey('new_key', $result);
        $this->assertEquals('value', $result['new_key']);
    }

    public function testRemoveUnmappedKeys(): void
    {
        $iterator = new class(['mapped' => 'value1', 'unmapped' => 'value2']) extends AbstractIterator {
            protected static $map = ['mapped' => 'mapped'];
            protected static $removeUnmapped = true;
        };

        $result = $iterator->getData();

        $this->assertArrayHasKey('mapped', $result);
        $this->assertArrayNotHasKey('unmapped', $result);
    }

    public function testKeepUnmappedKeys(): void
    {
        $iterator = new class(['mapped' => 'value1', 'unmapped' => 'value2']) extends AbstractIterator {
            protected static $map = ['mapped' => 'mapped'];
            protected static $removeUnmapped = false;
        };

        $result = $iterator->getData();

        $this->assertArrayHasKey('mapped', $result);
        $this->assertArrayHasKey('unmapped', $result);
    }
}
