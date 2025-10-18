<?php

namespace Exodus4D\ESI\Tests\Unit\Config\Ccp\Esi;

use Exodus4D\ESI\Config\Ccp\Esi\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Config::class)]
class ConfigTest extends TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        $this->config = new Config();
    }

    public function testGetEndpointsDataReturnsArray(): void
    {
        $endpoints = $this->config->getEndpointsData();

        $this->assertIsArray($endpoints);
        $this->assertNotEmpty($endpoints);
    }

    public function testGetEndpointsDataContainsValidStructure(): void
    {
        $endpoints = $this->config->getEndpointsData();

        foreach ($endpoints as $endpoint) {
            $this->assertArrayHasKey('method', $endpoint);
            $this->assertArrayHasKey('route', $endpoint);
            $this->assertArrayHasKey('version', $endpoint);
            $this->assertArrayHasKey('status', $endpoint);

            $this->assertIsString($endpoint['method']);
            $this->assertIsString($endpoint['route']);
            $this->assertNull($endpoint['status']);
        }
    }

    public function testGetEndpointsDataStripsVersionsFromRoutes(): void
    {
        $endpoints = $this->config->getEndpointsData();

        foreach ($endpoints as $endpoint) {
            // Routes should not start with /vX/
            $this->assertStringNotContainsString($endpoint['route'], '/v1/');
            $this->assertStringNotContainsString($endpoint['route'], '/v2/');
            $this->assertStringNotContainsString($endpoint['route'], '/v3/');
            $this->assertStringNotContainsString($endpoint['route'], '/v4/');
            $this->assertStringNotContainsString($endpoint['route'], '/v5/');
        }
    }

    public function testGetEndpointStatus(): void
    {
        $endpoint = $this->config->getEndpoint(['status', 'GET']);

        $this->assertEquals('/v1/status/', $endpoint);
    }

    public function testGetEndpointMetaStatus(): void
    {
        $endpoint = $this->config->getEndpoint(['meta', 'status', 'GET']);

        $this->assertEquals('/status.json', $endpoint);
    }

    public function testGetEndpointAlliances(): void
    {
        $endpoint = $this->config->getEndpoint(['alliances', 'GET'], ['99000001']);

        $this->assertEquals('/v3/alliances/99000001/', $endpoint);
    }

    public function testGetEndpointCorporations(): void
    {
        $endpoint = $this->config->getEndpoint(['corporations', 'GET'], ['98000001']);

        $this->assertEquals('/v4/corporations/98000001/', $endpoint);
    }

    public function testGetEndpointCorporationsNpccorps(): void
    {
        $endpoint = $this->config->getEndpoint(['corporations', 'npccorps', 'GET']);

        $this->assertEquals('/v1/corporations/npccorps/', $endpoint);
    }

    public function testGetEndpointCorporationsRoles(): void
    {
        $endpoint = $this->config->getEndpoint(['corporations', 'roles', 'GET'], ['98000001']);

        $this->assertEquals('/v1/corporations/98000001/roles/', $endpoint);
    }

    public function testGetEndpointCharacters(): void
    {
        $endpoint = $this->config->getEndpoint(['characters', 'GET'], ['95112526']);

        $this->assertEquals('/v5/characters/95112526/', $endpoint);
    }

    public function testGetEndpointCharactersAffiliation(): void
    {
        $endpoint = $this->config->getEndpoint(['characters', 'affiliation', 'POST']);

        $this->assertEquals('/v1/characters/affiliation/', $endpoint);
    }

    public function testGetEndpointCharactersClones(): void
    {
        $endpoint = $this->config->getEndpoint(['characters', 'clones', 'GET'], ['95112526']);

        $this->assertEquals('/v3/characters/95112526/clones/', $endpoint);
    }

    public function testGetEndpointCharactersLocation(): void
    {
        $endpoint = $this->config->getEndpoint(['characters', 'location', 'GET'], ['95112526']);

        $this->assertEquals('/v1/characters/95112526/location/', $endpoint);
    }

    public function testGetEndpointCharactersShip(): void
    {
        $endpoint = $this->config->getEndpoint(['characters', 'ship', 'GET'], ['95112526']);

        $this->assertEquals('/v1/characters/95112526/ship/', $endpoint);
    }

    public function testGetEndpointCharactersOnline(): void
    {
        $endpoint = $this->config->getEndpoint(['characters', 'online', 'GET'], ['95112526']);

        $this->assertEquals('/v2/characters/95112526/online/', $endpoint);
    }

    public function testGetEndpointCharactersRoles(): void
    {
        $endpoint = $this->config->getEndpoint(['characters', 'roles', 'GET'], ['95112526']);

        $this->assertEquals('/v2/characters/95112526/roles/', $endpoint);
    }

    public function testGetEndpointDogmaAttributes(): void
    {
        $endpoint = $this->config->getEndpoint(['dogma', 'attributes', 'GET'], ['1234']);

        $this->assertEquals('/v1/dogma/attributes/1234/', $endpoint);
    }

    public function testGetEndpointFwSystems(): void
    {
        $endpoint = $this->config->getEndpoint(['fw', 'systems', 'GET']);

        $this->assertEquals('/v2/fw/systems/', $endpoint);
    }

    public function testGetEndpointUniverseNames(): void
    {
        $endpoint = $this->config->getEndpoint(['universe', 'names', 'POST']);

        $this->assertEquals('/v3/universe/names/', $endpoint);
    }

    public function testGetEndpointUniverseFactionsList(): void
    {
        $endpoint = $this->config->getEndpoint(['universe', 'factions', 'list', 'GET']);

        $this->assertEquals('/v2/universe/factions/', $endpoint);
    }

    public function testGetEndpointUniverseSystemJumps(): void
    {
        $endpoint = $this->config->getEndpoint(['universe', 'system_jumps', 'GET']);

        $this->assertEquals('/v1/universe/system_jumps/', $endpoint);
    }

    public function testGetEndpointUniverseSystemKills(): void
    {
        $endpoint = $this->config->getEndpoint(['universe', 'system_kills', 'GET']);

        $this->assertEquals('/v2/universe/system_kills/', $endpoint);
    }

    public function testGetEndpointUniverseRacesList(): void
    {
        $endpoint = $this->config->getEndpoint(['universe', 'races', 'list', 'GET']);

        $this->assertEquals('/v1/universe/races/', $endpoint);
    }

    public function testGetEndpointUniverseRegions(): void
    {
        $endpoint = $this->config->getEndpoint(['universe', 'regions', 'GET'], ['10000002']);

        $this->assertEquals('/v1/universe/regions/10000002/', $endpoint);
    }

    public function testGetEndpointUniverseRegionsList(): void
    {
        $endpoint = $this->config->getEndpoint(['universe', 'regions', 'list', 'GET']);

        $this->assertEquals('/v1/universe/regions/', $endpoint);
    }

    public function testGetEndpointUniverseConstellations(): void
    {
        $endpoint = $this->config->getEndpoint(['universe', 'constellations', 'GET'], ['20000020']);

        $this->assertEquals('/v1/universe/constellations/20000020/', $endpoint);
    }

    public function testGetEndpointUniverseConstellationsList(): void
    {
        $endpoint = $this->config->getEndpoint(['universe', 'constellations', 'list', 'GET']);

        $this->assertEquals('/v1/universe/constellations/', $endpoint);
    }

    public function testGetEndpointUniverseSystems(): void
    {
        $endpoint = $this->config->getEndpoint(['universe', 'systems', 'GET'], ['30000142']);

        $this->assertEquals('/v4/universe/systems/30000142/', $endpoint);
    }

    public function testGetEndpointUniverseSystemsList(): void
    {
        $endpoint = $this->config->getEndpoint(['universe', 'systems', 'list', 'GET']);

        $this->assertEquals('/v1/universe/systems/', $endpoint);
    }

    public function testGetEndpointUniverseStars(): void
    {
        $endpoint = $this->config->getEndpoint(['universe', 'stars', 'GET'], ['40000001']);

        $this->assertEquals('/v1/universe/stars/40000001/', $endpoint);
    }

    public function testGetEndpointUniversePlanets(): void
    {
        $endpoint = $this->config->getEndpoint(['universe', 'planets', 'GET'], ['40009077']);

        $this->assertEquals('/v1/universe/planets/40009077/', $endpoint);
    }

    public function testGetEndpointUniverseStargates(): void
    {
        $endpoint = $this->config->getEndpoint(['universe', 'stargates', 'GET'], ['50000056']);

        $this->assertEquals('/v1/universe/stargates/50000056/', $endpoint);
    }

    public function testGetEndpointUniverseStations(): void
    {
        $endpoint = $this->config->getEndpoint(['universe', 'stations', 'GET'], ['60003760']);

        $this->assertEquals('/v2/universe/stations/60003760/', $endpoint);
    }

    public function testGetEndpointUniverseStructures(): void
    {
        $endpoint = $this->config->getEndpoint(['universe', 'structures', 'GET'], ['1022167642188']);

        $this->assertEquals('/v2/universe/structures/1022167642188/', $endpoint);
    }

    public function testGetEndpointUniverseCategories(): void
    {
        $endpoint = $this->config->getEndpoint(['universe', 'categories', 'GET'], ['6']);

        $this->assertEquals('/v1/universe/categories/6/', $endpoint);
    }

    public function testGetEndpointUniverseCategoriesList(): void
    {
        $endpoint = $this->config->getEndpoint(['universe', 'categories', 'list', 'GET']);

        $this->assertEquals('/v1/universe/categories/', $endpoint);
    }

    public function testGetEndpointUniverseGroups(): void
    {
        $endpoint = $this->config->getEndpoint(['universe', 'groups', 'GET'], ['25']);

        $this->assertEquals('/v1/universe/groups/25/', $endpoint);
    }

    public function testGetEndpointUniverseGroupsList(): void
    {
        $endpoint = $this->config->getEndpoint(['universe', 'groups', 'list', 'GET']);

        $this->assertEquals('/v1/universe/groups/', $endpoint);
    }

    public function testGetEndpointUniverseTypes(): void
    {
        $endpoint = $this->config->getEndpoint(['universe', 'types', 'GET'], ['34']);

        $this->assertEquals('/v3/universe/types/34/', $endpoint);
    }

    public function testGetEndpointRoutesWithMultiplePlaceholders(): void
    {
        $endpoint = $this->config->getEndpoint(['routes', 'POST'], ['30000142', '30000144']);

        $this->assertEquals('/route/30000142/30000144', $endpoint);
    }

    public function testGetEndpointUiAutopilotWaypoint(): void
    {
        $endpoint = $this->config->getEndpoint(['ui', 'autopilot', 'waypoint', 'POST']);

        $this->assertEquals('/v2/ui/autopilot/waypoint/', $endpoint);
    }

    public function testGetEndpointUiOpenwindowInformation(): void
    {
        $endpoint = $this->config->getEndpoint(['ui', 'openwindow', 'information', 'POST']);

        $this->assertEquals('/v1/ui/openwindow/information/', $endpoint);
    }

    public function testGetEndpointSovereigntyMap(): void
    {
        $endpoint = $this->config->getEndpoint(['sovereignty', 'map', 'GET']);

        $this->assertEquals('/v1/sovereignty/map/', $endpoint);
    }

    public function testGetEndpointSearch(): void
    {
        $endpoint = $this->config->getEndpoint(['search', 'GET'], ['95112526']);

        $this->assertEquals('/v3/characters/95112526/search/', $endpoint);
    }

    public function testGetEndpointWithInvalidPathThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->config->getEndpoint(['invalid', 'endpoint']);
    }
}
