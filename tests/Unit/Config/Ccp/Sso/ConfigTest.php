<?php

namespace Exodus4D\ESI\Tests\Unit\Config\Ccp\Sso;

use Exodus4D\ESI\Config\Ccp\Sso\Config;
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
        }
    }

    public function testGetEndpointJwks(): void
    {
        $endpoint = $this->config->getEndpoint(['jwks', 'GET']);

        $this->assertEquals('/oauth/jwks', $endpoint);
    }

    public function testGetEndpointWithInvalidPathThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->config->getEndpoint(['invalid', 'endpoint']);
    }
}
