<?php

namespace Exodus4D\ESI\Tests\Unit\Client\Ccp\Esi;

use Exodus4D\ESI\Client\Ccp\Esi\Esi;
use Exodus4D\ESI\Config\ConfigInterface;
use Exodus4D\ESI\Lib\RequestConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Esi::class)]
class EsiTest extends TestCase
{
    private function createTestEsi(string $url = 'https://esi.evetech.net'): Esi
    {
        return new class($url) extends Esi {
            // Expose protected methods for testing
            public function publicFormatUrlParams(array $query = [], array $format = []): array {
                return $this->formatUrlParams($query, $format);
            }

            public function publicGetEndpointURI(array $path = [], array $placeholders = []): string {
                return $this->getEndpointURI($path, $placeholders);
            }

            public function publicGetRequestOptions(string $accessToken = '', $content = null, array $query = []): array {
                return $this->getRequestOptions($accessToken, $content, $query);
            }

            public function publicGetConfig(): ConfigInterface {
                return $this->getConfig();
            }

            public function publicGetServerStatusRequest(): RequestConfig {
                return $this->getServerStatusRequest();
            }

            public function publicGetCharacterRequest(int $characterId): RequestConfig {
                return $this->getCharacterRequest($characterId);
            }

            public function publicGetCorporationRequest(int $corporationId): RequestConfig {
                return $this->getCorporationRequest($corporationId);
            }

            public function publicGetAllianceRequest(int $allianceId): RequestConfig {
                return $this->getAllianceRequest($allianceId);
            }

            public function publicGetUniverseSystemRequest(int $systemId): RequestConfig {
                return $this->getUniverseSystemRequest($systemId);
            }
        };
    }

    public function testConstructorSetsUrl(): void
    {
        $esi = $this->createTestEsi('https://test.esi.com');

        $this->assertEquals('https://test.esi.com', $esi->getUrl());
    }

    public function testSetAndGetDataSource(): void
    {
        $esi = $this->createTestEsi();
        $esi->setDataSource('singularity');

        $this->assertEquals('singularity', $esi->getDataSource());
    }

    public function testSetAndGetVersion(): void
    {
        $esi = $this->createTestEsi();
        $esi->setVersion('v2');

        $this->assertEquals('v2', $esi->getVersion());
    }

    public function testGetConfigReturnsConfigInterface(): void
    {
        $esi = $this->createTestEsi();
        $config = $esi->publicGetConfig();

        $this->assertInstanceOf(ConfigInterface::class, $config);
    }

    public function testGetConfigReturnsSameInstance(): void
    {
        $esi = $this->createTestEsi();
        $config1 = $esi->publicGetConfig();
        $config2 = $esi->publicGetConfig();

        $this->assertSame($config1, $config2);
    }

    public function testGetRequestOptionsWithAccessToken(): void
    {
        $esi = $this->createTestEsi();
        $options = $esi->publicGetRequestOptions('test_token_123');

        $this->assertArrayHasKey('headers', $options);
        $this->assertArrayHasKey('Authorization', $options['headers']);
        $this->assertEquals('Bearer test_token_123', $options['headers']['Authorization']);
        $this->assertArrayHasKey('X-Compatibility-Date', $options['headers']);
    }

    public function testGetRequestOptionsWithContent(): void
    {
        $esi = $this->createTestEsi();
        $content = ['key' => 'value'];
        $options = $esi->publicGetRequestOptions('', $content);

        $this->assertArrayHasKey('json', $options);
        $this->assertEquals($content, $options['json']);
    }

    public function testGetRequestOptionsWithQuery(): void
    {
        $esi = $this->createTestEsi();
        $query = ['page' => 1, 'limit' => 50];
        $options = $esi->publicGetRequestOptions('', null, $query);

        $this->assertArrayHasKey('query', $options);
        $this->assertEquals($query, $options['query']);
    }

    public function testGetRequestOptionsWithDataSource(): void
    {
        $esi = $this->createTestEsi();
        $esi->setDataSource('tranquility');
        $options = $esi->publicGetRequestOptions();

        $this->assertArrayHasKey('query', $options);
        $this->assertArrayHasKey('datasource', $options['query']);
        $this->assertEquals('tranquility', $options['query']['datasource']);
    }

    public function testGetRequestOptionsWithAllParameters(): void
    {
        $esi = $this->createTestEsi();
        $esi->setDataSource('singularity');
        $options = $esi->publicGetRequestOptions('token', ['data' => 'value'], ['page' => 1]);

        $this->assertArrayHasKey('headers', $options);
        $this->assertArrayHasKey('Authorization', $options['headers']);
        $this->assertEquals('Bearer token', $options['headers']['Authorization']);
        $this->assertArrayHasKey('json', $options);
        $this->assertEquals(['data' => 'value'], $options['json']);
        $this->assertArrayHasKey('query', $options);
        $this->assertEquals('singularity', $options['query']['datasource']);
        $this->assertEquals(1, $options['query']['page']);
    }

    public function testGetEndpointURIReturnsString(): void
    {
        $esi = $this->createTestEsi();
        $uri = $esi->publicGetEndpointURI(['status', 'GET']);

        $this->assertIsString($uri);
        $this->assertNotEmpty($uri);
    }

    public function testGetEndpointURIWithPlaceholders(): void
    {
        $esi = $this->createTestEsi();
        $uri = $esi->publicGetEndpointURI(['characters', 'GET'], [95112526]);

        $this->assertIsString($uri);
        $this->assertStringContainsString('95112526', $uri);
    }

    public function testGetEndpointURIWithVersionOverride(): void
    {
        $esi = $this->createTestEsi();
        $esi->setVersion('v2');
        $uri = $esi->publicGetEndpointURI(['status', 'GET']);

        $this->assertIsString($uri);
        $this->assertStringContainsString('v2', $uri);
    }

    public function testFormatUrlParamsWithSimpleArray(): void
    {
        $esi = $this->createTestEsi();
        $query = ['categories' => ['character', 'corporation']];
        $format = ['categories' => [',']];

        $result = $esi->publicFormatUrlParams($query, $format);

        $this->assertArrayHasKey('categories', $result);
        $this->assertEquals('character,corporation', $result['categories']);
    }

    public function testFormatUrlParamsWithoutFormat(): void
    {
        $esi = $this->createTestEsi();
        $query = ['key' => 'value'];

        $result = $esi->publicFormatUrlParams($query, []);

        $this->assertEquals($query, $result);
    }

    public function testGetServerStatusRequestReturnsRequestConfig(): void
    {
        $esi = $this->createTestEsi();
        $config = $esi->publicGetServerStatusRequest();

        $this->assertInstanceOf(RequestConfig::class, $config);
        $this->assertNotNull($config->getRequest());
        $this->assertEquals('GET', $config->getRequest()->getMethod());
    }

    public function testGetCharacterRequestReturnsRequestConfig(): void
    {
        $esi = $this->createTestEsi();
        $config = $esi->publicGetCharacterRequest(95112526);

        $this->assertInstanceOf(RequestConfig::class, $config);
        $this->assertNotNull($config->getRequest());
        $this->assertEquals('GET', $config->getRequest()->getMethod());
        $this->assertStringContainsString('95112526', (string)$config->getRequest()->getUri());
    }

    public function testGetCorporationRequestReturnsRequestConfig(): void
    {
        $esi = $this->createTestEsi();
        $config = $esi->publicGetCorporationRequest(98000001);

        $this->assertInstanceOf(RequestConfig::class, $config);
        $this->assertEquals('GET', $config->getRequest()->getMethod());
    }

    public function testGetAllianceRequestReturnsRequestConfig(): void
    {
        $esi = $this->createTestEsi();
        $config = $esi->publicGetAllianceRequest(99000001);

        $this->assertInstanceOf(RequestConfig::class, $config);
        $this->assertEquals('GET', $config->getRequest()->getMethod());
    }

    public function testGetUniverseSystemRequestReturnsRequestConfig(): void
    {
        $esi = $this->createTestEsi();
        $config = $esi->publicGetUniverseSystemRequest(30000142);

        $this->assertInstanceOf(RequestConfig::class, $config);
        $this->assertEquals('GET', $config->getRequest()->getMethod());
        $this->assertStringContainsString('30000142', (string)$config->getRequest()->getUri());
    }

    public function testRequestConfigHasFormatter(): void
    {
        $esi = $this->createTestEsi();
        $config = $esi->publicGetServerStatusRequest();

        $this->assertIsCallable($config->getFormatter());
    }
}
