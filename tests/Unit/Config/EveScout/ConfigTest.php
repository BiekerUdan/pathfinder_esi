<?php

namespace Exodus4D\ESI\Tests\Unit\Config\EveScout;

use Exodus4D\ESI\Config\EveScout\Config;
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
        $this->assertCount(1, $endpoints);
    }

    public function testGetEndpointsDataContainsValidStructure(): void
    {
        $endpoints = $this->config->getEndpointsData();

        foreach ($endpoints as $endpoint) {
            $this->assertArrayHasKey('method', $endpoint);
            $this->assertArrayHasKey('route', $endpoint);
            $this->assertArrayHasKey('version', $endpoint);
            $this->assertArrayHasKey('status', $endpoint);
        }
    }

    public function testGetEndpointSignatures(): void
    {
        $endpoint = $this->config->getEndpoint(['signatures', 'GET']);

        $this->assertEquals('v2/public/signatures', $endpoint);
    }

    public function testGetEndpointSignaturesHasVersionInRoute(): void
    {
        $endpoints = $this->config->getEndpointsData();

        $this->assertCount(1, $endpoints);
        $this->assertEquals('get', $endpoints[0]['method']);
        $this->assertEquals('v2/public/signatures', $endpoints[0]['route']);
        // This endpoint has v2 in the route path, not as a version prefix
        $this->assertNull($endpoints[0]['version']);
    }

    public function testGetEndpointWithInvalidPathThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->config->getEndpoint(['invalid', 'endpoint']);
    }
}
