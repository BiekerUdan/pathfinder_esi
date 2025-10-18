<?php

namespace Exodus4D\ESI\Tests\Unit\Config\GitHub;

use Exodus4D\ESI\Config\GitHub\Config;
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
        $this->assertCount(2, $endpoints);
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

    public function testGetEndpointReleases(): void
    {
        $endpoint = $this->config->getEndpoint(['releases', 'GET'], ['owner/repo']);

        $this->assertEquals('/repos/owner/repo/releases', $endpoint);
    }

    public function testGetEndpointMarkdown(): void
    {
        $endpoint = $this->config->getEndpoint(['markdown', 'POST']);

        $this->assertEquals('/markdown', $endpoint);
    }

    public function testGetEndpointWithInvalidPathThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->config->getEndpoint(['invalid', 'endpoint']);
    }
}
