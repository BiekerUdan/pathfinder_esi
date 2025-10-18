<?php

namespace Exodus4D\ESI\Tests\Unit\Mapper\Esi;

use Exodus4D\ESI\Mapper\Esi\Alliance\Alliance;
use Exodus4D\ESI\Mapper\Esi\Character\Character;
use Exodus4D\ESI\Mapper\Esi\Corporation\Corporation;
use Exodus4D\ESI\Mapper\Esi\Universe\System;
use Exodus4D\ESI\Mapper\Esi\Universe\Region;
use Exodus4D\ESI\Mapper\Esi\Universe\Constellation;
use Exodus4D\ESI\Mapper\Esi\Universe\Station;
use Exodus4D\ESI\Mapper\Esi\Universe\Stargate;
use Exodus4D\ESI\Mapper\Esi\Universe\Star;
use Exodus4D\ESI\Mapper\Esi\Universe\Planet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Alliance::class)]
#[CoversClass(Character::class)]
#[CoversClass(Corporation::class)]
#[CoversClass(System::class)]
#[CoversClass(Region::class)]
#[CoversClass(Constellation::class)]
#[CoversClass(Station::class)]
#[CoversClass(Stargate::class)]
#[CoversClass(Star::class)]
#[CoversClass(Planet::class)]
class SimpleMapperTest extends TestCase
{
    public function testAllianceMapper(): void
    {
        $input = [
            'id' => 99000001,
            'name' => 'Test Alliance',
            'ticker' => 'TEST',
            'date_founded' => '2010-01-01',
            'faction_id' => 500001
        ];

        $mapper = new Alliance($input);
        $result = $mapper->getData();

        $this->assertEquals(99000001, $result['id']);
        $this->assertEquals('Test Alliance', $result['name']);
        $this->assertEquals('TEST', $result['ticker']);
        $this->assertEquals('2010-01-01', $result['dateFounded']);
        $this->assertEquals(500001, $result['factionId']);
    }

    public function testCharacterMapper(): void
    {
        $input = [
            'id' => 95112526,
            'name' => 'Test Character',
            'birthday' => '2010-01-01',
            'gender' => 'male',
            'security_status' => 5.0,
            'race_id' => 2,
            'bloodline_id' => 4,
            'ancestry_id' => 24,
            'corporation_id' => 98000001,
            'alliance_id' => 99000001
        ];

        $mapper = new Character($input);
        $result = $mapper->getData();

        $this->assertEquals(95112526, $result['id']);
        $this->assertEquals('Test Character', $result['name']);
        $this->assertEquals('2010-01-01', $result['birthday']);
        $this->assertEquals('male', $result['gender']);
        $this->assertEquals(5.0, $result['securityStatus']);
        $this->assertEquals(2, $result['race']['id']);
        $this->assertEquals(4, $result['bloodline']['id']);
        $this->assertEquals(24, $result['ancestry']['id']);
        $this->assertEquals(98000001, $result['corporation']['id']);
        $this->assertEquals(99000001, $result['alliance']['id']);
    }

    public function testCorporationMapper(): void
    {
        $input = [
            'id' => 98000001,
            'name' => 'Test Corp',
            'ticker' => 'TCORP',
            'date_founded' => '2010-01-01',
            'member_count' => 150,
            'faction_id' => 500001,
            'alliance_id' => 99000001
        ];

        $mapper = new Corporation($input);
        $result = $mapper->getData();

        $this->assertEquals(98000001, $result['id']);
        $this->assertEquals('Test Corp', $result['name']);
        $this->assertEquals('TCORP', $result['ticker']);
        $this->assertEquals('2010-01-01', $result['dateFounded']);
        $this->assertEquals(150, $result['memberCount']);
        $this->assertEquals(500001, $result['factionId']);
        $this->assertEquals(99000001, $result['allianceId']);
    }

    public function testSystemMapper(): void
    {
        $input = [
            'system_id' => 30000142,
            'name' => 'Jita',
            'constellation_id' => 20000020,
            'security_class' => 'B',
            'security_status' => 0.9459,
            'star_id' => 40009077,
            'planets' => [40009078, 40009079],
            'stargates' => [50000056, 50000057],
            'stations' => [60003760],
            'position' => ['x' => 1.0, 'y' => 2.0, 'z' => 3.0]
        ];

        $mapper = new System($input);
        $result = $mapper->getData();

        $this->assertEquals(30000142, $result['id']);
        $this->assertEquals('Jita', $result['name']);
        $this->assertEquals(20000020, $result['constellationId']);
        $this->assertEquals('B', $result['securityClass']);
        $this->assertEquals(0.9459, $result['securityStatus']);
        $this->assertEquals(40009077, $result['starId']);
        $this->assertEquals([40009078, 40009079], $result['planets']);
        $this->assertEquals([50000056, 50000057], $result['stargates']);
        $this->assertEquals([60003760], $result['stations']);
    }

    public function testRegionMapper(): void
    {
        $input = [
            'region_id' => 10000002,
            'name' => 'The Forge',
            'description' => 'A major trade hub region',
            'constellations' => [20000020]
        ];

        $mapper = new Region($input);
        $result = $mapper->getData();

        $this->assertEquals(10000002, $result['id']);
        $this->assertEquals('The Forge', $result['name']);
        $this->assertEquals('A major trade hub region', $result['description']);
        $this->assertEquals([20000020], $result['constellations']);
    }

    public function testConstellationMapper(): void
    {
        $input = [
            'constellation_id' => 20000020,
            'name' => 'Kimotoro',
            'region_id' => 10000002,
            'systems' => [30000142, 30000143],
            'position' => ['x' => 1.0, 'y' => 2.0, 'z' => 3.0]
        ];

        $mapper = new Constellation($input);
        $result = $mapper->getData();

        $this->assertEquals(20000020, $result['id']);
        $this->assertEquals('Kimotoro', $result['name']);
        $this->assertEquals(10000002, $result['regionId']);
        $this->assertEquals([30000142, 30000143], $result['systems']);
    }

    public function testStationMapper(): void
    {
        $input = [
            'station_id' => 60003760,
            'name' => 'Jita IV - Moon 4 - Caldari Navy Assembly Plant',
            'system_id' => 30000142,
            'type_id' => 52678,
            'race_id' => 1,
            'owner' => 1000035,
            'services' => ['market', 'reprocessing-plant'],
            'position' => ['x' => 1.0, 'y' => 2.0, 'z' => 3.0]
        ];

        $mapper = new Station($input);
        $result = $mapper->getData();

        $this->assertEquals(60003760, $result['id']);
        $this->assertEquals('Jita IV - Moon 4 - Caldari Navy Assembly Plant', $result['name']);
        $this->assertEquals(30000142, $result['systemId']);
        $this->assertEquals(52678, $result['typeId']);
        $this->assertEquals(1, $result['raceId']);
        $this->assertEquals(1000035, $result['corporationId']); // owner maps to corporationId
        $this->assertEquals(['market', 'reprocessing-plant'], $result['services']);
    }

    public function testStargateMapper(): void
    {
        $input = [
            'stargate_id' => 50000056,
            'name' => 'Stargate (Perimeter)',
            'system_id' => 30000142,
            'type_id' => 29624,
            'destination' => [
                'system_id' => 30000144,
                'stargate_id' => 50000342
            ],
            'position' => ['x' => 1.0, 'y' => 2.0, 'z' => 3.0]
        ];

        $mapper = new Stargate($input);
        $result = $mapper->getData();

        $this->assertEquals(50000056, $result['id']);
        $this->assertEquals('Stargate (Perimeter)', $result['name']);
        $this->assertEquals(30000142, $result['systemId']);
        $this->assertEquals(29624, $result['typeId']);
        $this->assertArrayHasKey('destination', $result);
    }

    public function testStarMapper(): void
    {
        $input = [
            'name' => 'Jita - Star',
            'type_id' => 45041,
            'age' => 9116000000,
            'luminosity' => 0.01121,
            'radius' => 62900000,
            'spectral_class' => 'K2 V',
            'temperature' => 3953
        ];

        $mapper = new Star($input);
        $result = $mapper->getData();

        $this->assertEquals('Jita - Star', $result['name']);
        $this->assertEquals(45041, $result['typeId']);
        $this->assertEquals(9116000000, $result['age']);
        $this->assertEquals(0.01121, $result['luminosity']);
        $this->assertEquals(62900000, $result['radius']);
        $this->assertEquals('K2 V', $result['spectralClass']);
        $this->assertEquals(3953, $result['temperature']);
    }

    public function testPlanetMapper(): void
    {
        $input = [
            'planet_id' => 40009078,
            'name' => 'Jita I',
            'type_id' => 2016,
            'system_id' => 30000142,
            'position' => ['x' => 1.0, 'y' => 2.0, 'z' => 3.0]
        ];

        $mapper = new Planet($input);
        $result = $mapper->getData();

        $this->assertEquals(40009078, $result['id']);
        $this->assertEquals('Jita I', $result['name']);
        $this->assertEquals(2016, $result['typeId']);
        $this->assertEquals(30000142, $result['systemId']);
    }
}
