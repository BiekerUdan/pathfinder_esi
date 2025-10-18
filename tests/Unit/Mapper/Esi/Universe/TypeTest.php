<?php

namespace Exodus4D\ESI\Tests\Unit\Mapper\Esi\Universe;

use Exodus4D\ESI\Mapper\Esi\Universe\Type;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Type::class)]
class TypeTest extends TestCase
{
    public function testGetDataWithBasicFields(): void
    {
        $input = [
            'type_id' => 34,
            'name' => 'Tritanium',
            'description' => 'A basic ore',
            'published' => true,
            'group_id' => 18,
            'volume' => 0.01
        ];

        $mapper = new Type($input);
        $result = $mapper->getData();

        $this->assertEquals(34, $result['id']);
        $this->assertEquals('Tritanium', $result['name']);
        $this->assertEquals('A basic ore', $result['description']);
        $this->assertTrue($result['published']);
        $this->assertEquals(18, $result['groupId']);
        $this->assertEquals(0.01, $result['volume']);
    }

    public function testGetDataWithAllFields(): void
    {
        $input = [
            'type_id' => 34,
            'name' => 'Tritanium',
            'description' => 'A basic ore',
            'published' => true,
            'group_id' => 18,
            'market_group_id' => 1857,
            'radius' => 1.0,
            'volume' => 0.01,
            'packaged_volume' => 0.0,
            'capacity' => 0.0,
            'portion_size' => 1,
            'mass' => 1.0,
            'graphic_id' => 123
        ];

        $mapper = new Type($input);
        $result = $mapper->getData();

        $this->assertEquals(34, $result['id']);
        $this->assertEquals('Tritanium', $result['name']);
        $this->assertEquals(1857, $result['marketGroupId']);
        $this->assertEquals(1.0, $result['radius']);
        $this->assertEquals(0.01, $result['volume']);
        $this->assertEquals(0.0, $result['packagedVolume']);
        $this->assertEquals(0.0, $result['capacity']);
        $this->assertEquals(1, $result['portionSize']);
        $this->assertEquals(1.0, $result['mass']);
        $this->assertEquals(123, $result['graphicId']);
    }

    public function testGetDataWithDogmaAttributes(): void
    {
        $dogmaData = [
            (object)['attribute_id' => 182, 'value' => 30.0],
            (object)['attribute_id' => 183, 'value' => 150.0]
        ];

        $input = [
            'type_id' => 587,
            'name' => 'Rifter',
            'dogma_attributes' => $dogmaData
        ];

        $mapper = new Type($input);
        $result = $mapper->getData();

        $this->assertArrayHasKey('dogma_attributes', $result);
        $this->assertIsArray($result['dogma_attributes']);
        $this->assertCount(2, $result['dogma_attributes']);

        $this->assertEquals(182, $result['dogma_attributes'][0]['attributeId']);
        $this->assertEquals(30.0, $result['dogma_attributes'][0]['value']);

        $this->assertEquals(183, $result['dogma_attributes'][1]['attributeId']);
        $this->assertEquals(150.0, $result['dogma_attributes'][1]['value']);
    }

    public function testGetDataWithEmptyDogmaAttributes(): void
    {
        $input = [
            'type_id' => 34,
            'name' => 'Tritanium',
            'dogma_attributes' => []
        ];

        $mapper = new Type($input);
        $result = $mapper->getData();

        $this->assertArrayHasKey('dogma_attributes', $result);
        $this->assertIsArray($result['dogma_attributes']);
        $this->assertEmpty($result['dogma_attributes']);
    }

    public function testGetDataNormalizesDogmaAttributeTypes(): void
    {
        $dogmaData = [
            (object)['attribute_id' => '182', 'value' => '30.5']
        ];

        $input = [
            'type_id' => 587,
            'name' => 'Rifter',
            'dogma_attributes' => $dogmaData
        ];

        $mapper = new Type($input);
        $result = $mapper->getData();

        // Should convert string to int and float
        $this->assertIsInt($result['dogma_attributes'][0]['attributeId']);
        $this->assertIsFloat($result['dogma_attributes'][0]['value']);
        $this->assertEquals(182, $result['dogma_attributes'][0]['attributeId']);
        $this->assertEquals(30.5, $result['dogma_attributes'][0]['value']);
    }

    public function testGetDataWithMixedFieldsAndDogma(): void
    {
        $dogmaData = [
            (object)['attribute_id' => 182, 'value' => 30.0]
        ];

        $input = [
            'type_id' => 587,
            'name' => 'Rifter',
            'description' => 'A Minmatar frigate',
            'published' => true,
            'group_id' => 25,
            'volume' => 27289.0,
            'dogma_attributes' => $dogmaData
        ];

        $mapper = new Type($input);
        $result = $mapper->getData();

        // Check regular fields
        $this->assertEquals(587, $result['id']);
        $this->assertEquals('Rifter', $result['name']);
        $this->assertEquals('A Minmatar frigate', $result['description']);
        $this->assertTrue($result['published']);
        $this->assertEquals(25, $result['groupId']);
        $this->assertEquals(27289.0, $result['volume']);

        // Check dogma attributes
        $this->assertCount(1, $result['dogma_attributes']);
        $this->assertEquals(182, $result['dogma_attributes'][0]['attributeId']);
        $this->assertEquals(30.0, $result['dogma_attributes'][0]['value']);
    }
}
