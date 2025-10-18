<?php

namespace Exodus4D\ESI\Tests\Unit\Config;

use Exodus4D\ESI\Config\AbstractConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractConfig::class)]
class AbstractConfigTest extends TestCase
{
    private function createTestConfig(array $spec = [])
    {
        return new class($spec) extends AbstractConfig {
            public function __construct(array $spec)
            {
                static::$spec = $spec;
            }

            // Expose protected method for testing
            public function publicStripVersion(string &$endpoint): ?string
            {
                return $this->stripVersion($endpoint);
            }
        };
    }

    public function testGetEndpointsDataWithSimpleEndpoints(): void
    {
        $spec = [
            'status' => [
                'GET' => '/v1/status/'
            ],
            'alliances' => [
                'GET' => '/v3/alliances/{x}/'
            ]
        ];

        $config = $this->createTestConfig($spec);
        $endpoints = $config->getEndpointsData();

        $this->assertCount(2, $endpoints);

        // Check first endpoint
        $this->assertEquals('get', $endpoints[0]['method']);
        $this->assertEquals('/status/', $endpoints[0]['route']);
        $this->assertEquals('v1', $endpoints[0]['version']);
        $this->assertNull($endpoints[0]['status']);

        // Check second endpoint
        $this->assertEquals('get', $endpoints[1]['method']);
        $this->assertEquals('/alliances/{x}/', $endpoints[1]['route']);
        $this->assertEquals('v3', $endpoints[1]['version']);
    }

    public function testGetEndpointsDataWithNestedEndpoints(): void
    {
        $spec = [
            'characters' => [
                'GET' => '/v5/characters/{x}/',
                'location' => [
                    'GET' => '/v1/characters/{x}/location/'
                ],
                'ship' => [
                    'GET' => '/v1/characters/{x}/ship/'
                ]
            ]
        ];

        $config = $this->createTestConfig($spec);
        $endpoints = $config->getEndpointsData();

        $this->assertCount(3, $endpoints);

        // All should have versions stripped
        foreach ($endpoints as $endpoint) {
            $this->assertArrayHasKey('method', $endpoint);
            $this->assertArrayHasKey('route', $endpoint);
            $this->assertArrayHasKey('version', $endpoint);
            $this->assertArrayHasKey('status', $endpoint);
            $this->assertStringStartsWith('/characters/', $endpoint['route']);
        }
    }

    public function testGetEndpointsDataWithMixedMethods(): void
    {
        $spec = [
            'characters' => [
                'affiliation' => [
                    'POST' => '/v1/characters/affiliation/'
                ]
            ],
            'routes' => [
                'POST' => '/route/{x}/{x}'
            ]
        ];

        $config = $this->createTestConfig($spec);
        $endpoints = $config->getEndpointsData();

        $this->assertCount(2, $endpoints);

        // Check POST methods are lowercase
        $this->assertEquals('post', $endpoints[0]['method']);
        $this->assertEquals('post', $endpoints[1]['method']);
    }

    public function testGetEndpointsDataIgnoresNonStringValues(): void
    {
        $spec = [
            'test' => [
                'GET' => '/v1/test/',
                'nested' => [
                    'invalid' => null,
                    'GET' => '/v1/nested/'
                ]
            ]
        ];

        $config = $this->createTestConfig($spec);
        $endpoints = $config->getEndpointsData();

        // Should only get the 2 valid endpoints, null should be ignored
        $this->assertCount(2, $endpoints);
    }

    public function testGetEndpointWithSimplePath(): void
    {
        $spec = [
            'status' => [
                'GET' => '/v1/status/'
            ]
        ];

        $config = $this->createTestConfig($spec);
        $endpoint = $config->getEndpoint(['status', 'GET']);

        $this->assertEquals('/v1/status/', $endpoint);
    }

    public function testGetEndpointWithNestedPath(): void
    {
        $spec = [
            'characters' => [
                'location' => [
                    'GET' => '/v1/characters/{x}/location/'
                ]
            ]
        ];

        $config = $this->createTestConfig($spec);
        $endpoint = $config->getEndpoint(['characters', 'location', 'GET']);

        $this->assertEquals('/v1/characters/{x}/location/', $endpoint);
    }

    public function testGetEndpointWithSinglePlaceholder(): void
    {
        $spec = [
            'alliances' => [
                'GET' => '/v3/alliances/{x}/'
            ]
        ];

        $config = $this->createTestConfig($spec);
        $endpoint = $config->getEndpoint(['alliances', 'GET'], ['123456']);

        $this->assertEquals('/v3/alliances/123456/', $endpoint);
    }

    public function testGetEndpointWithMultiplePlaceholders(): void
    {
        $spec = [
            'routes' => [
                'POST' => '/route/{x}/{x}'
            ]
        ];

        $config = $this->createTestConfig($spec);
        $endpoint = $config->getEndpoint(['routes', 'POST'], ['30000142', '30000144']);

        $this->assertEquals('/route/30000142/30000144', $endpoint);
    }

    public function testGetEndpointWithInvalidPathThrowsException(): void
    {
        $spec = [
            'status' => [
                'GET' => '/v1/status/'
            ]
        ];

        $config = $this->createTestConfig($spec);

        $this->expectException(\InvalidArgumentException::class);
        $config->getEndpoint(['invalid', 'path']);
    }

    public function testGetEndpointWithPartialInvalidPathThrowsException(): void
    {
        $spec = [
            'characters' => [
                'location' => [
                    'GET' => '/v1/characters/{x}/location/'
                ]
            ]
        ];

        $config = $this->createTestConfig($spec);

        $this->expectException(\InvalidArgumentException::class);
        $config->getEndpoint(['characters', 'invalid', 'GET']);
    }

    public function testGetEndpointReturnsEmptyStringForNonStringSpec(): void
    {
        $spec = [
            'test' => [
                'nested' => []
            ]
        ];

        $config = $this->createTestConfig($spec);
        $endpoint = $config->getEndpoint(['test', 'nested']);

        $this->assertEquals('', $endpoint);
    }

    public function testStripVersionRemovesV1(): void
    {
        $config = $this->createTestConfig([]);
        $endpoint = '/v1/status/';

        $version = $config->publicStripVersion($endpoint);

        $this->assertEquals('v1', $version);
        $this->assertEquals('/status/', $endpoint);
    }

    public function testStripVersionRemovesV2(): void
    {
        $config = $this->createTestConfig([]);
        $endpoint = '/v2/characters/{x}/online/';

        $version = $config->publicStripVersion($endpoint);

        $this->assertEquals('v2', $version);
        $this->assertEquals('/characters/{x}/online/', $endpoint);
    }

    public function testStripVersionRemovesV3(): void
    {
        $config = $this->createTestConfig([]);
        $endpoint = '/v3/alliances/{x}/';

        $version = $config->publicStripVersion($endpoint);

        $this->assertEquals('v3', $version);
        $this->assertEquals('/alliances/{x}/', $endpoint);
    }

    public function testStripVersionHandlesHigherVersionNumbers(): void
    {
        $config = $this->createTestConfig([]);
        $endpoint = '/v5/characters/{x}/';

        $version = $config->publicStripVersion($endpoint);

        $this->assertEquals('v5', $version);
        $this->assertEquals('/characters/{x}/', $endpoint);
    }

    public function testStripVersionReturnsNullWhenNoVersion(): void
    {
        $config = $this->createTestConfig([]);
        $endpoint = '/status.json';

        $version = $config->publicStripVersion($endpoint);

        $this->assertNull($version);
        $this->assertEquals('/status.json', $endpoint);
    }

    public function testStripVersionOnlyRemovesFirstOccurrence(): void
    {
        $config = $this->createTestConfig([]);
        $endpoint = '/v1/test/v2/nested/';

        $version = $config->publicStripVersion($endpoint);

        $this->assertEquals('v1', $version);
        $this->assertEquals('/test/v2/nested/', $endpoint);
    }

    public function testGetEndpointTrimsWhitespace(): void
    {
        $spec = [
            'test' => [
                'GET' => '  /v1/test/  '
            ]
        ];

        $config = $this->createTestConfig($spec);
        $endpoint = $config->getEndpoint(['test', 'GET']);

        $this->assertEquals('/v1/test/', $endpoint);
    }

    public function testGetEndpointsDataWithEndpointWithoutVersion(): void
    {
        $spec = [
            'meta' => [
                'status' => [
                    'GET' => '/status.json'
                ]
            ]
        ];

        $config = $this->createTestConfig($spec);
        $endpoints = $config->getEndpointsData();

        $this->assertCount(1, $endpoints);
        $this->assertEquals('get', $endpoints[0]['method']);
        $this->assertEquals('/status.json', $endpoints[0]['route']);
        $this->assertNull($endpoints[0]['version']);
    }

    public function testGetEndpointsDataWithDeeplyNestedStructure(): void
    {
        $spec = [
            'universe' => [
                'factions' => [
                    'list' => [
                        'GET' => '/v2/universe/factions/'
                    ]
                ]
            ]
        ];

        $config = $this->createTestConfig($spec);
        $endpoints = $config->getEndpointsData();

        $this->assertCount(1, $endpoints);
        $this->assertEquals('get', $endpoints[0]['method']);
        $this->assertEquals('/universe/factions/', $endpoints[0]['route']);
        $this->assertEquals('v2', $endpoints[0]['version']);
    }
}
