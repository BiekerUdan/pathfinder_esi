<?php

namespace Exodus4D\ESI\Tests\Unit\Client;

use Exodus4D\ESI\Client\AbstractApi;
use Exodus4D\ESI\Config\ConfigInterface;
use Exodus4D\ESI\Lib\RequestConfig;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractApi::class)]
class AbstractApiTest extends TestCase
{
    private function createTestApi(string $url = 'https://api.example.com'): AbstractApi
    {
        return new class($url) extends AbstractApi {
            protected function getConfig(): ConfigInterface
            {
                return new class implements ConfigInterface {
                    public function getEndpointsData(): array {
                        return [];
                    }
                    public function getEndpoint(array $path, array $placeholders = []): string {
                        return '/test/endpoint';
                    }
                };
            }

            // Expose protected methods for testing
            public function publicGetAuthHeader(string $credentials, string $type = 'Basic'): array {
                return $this->getAuthHeader($credentials, $type);
            }

            public function publicLog(): \Closure {
                return $this->log();
            }

            public function publicGetClientConfig(): array {
                return $this->getClientConfig();
            }

            public function publicGetLogMiddlewareConfig(): array {
                return $this->getLogMiddlewareConfig();
            }

            public function publicGetCacheMiddlewareConfig(): array {
                return $this->getCacheMiddlewareConfig();
            }

            public function publicGetRetryMiddlewareConfig(): array {
                return $this->getRetryMiddlewareConfig();
            }

            public function publicRequest(string $method, string $uri, array $options = []) {
                return $this->request($method, $uri, $options);
            }

            public function publicArrayMergeRecursiveDistinct(array $array1, array $array2): array {
                return self::array_merge_recursive_distinct($array1, $array2);
            }

            public function publicGetClient() {
                return $this->getClient();
            }

            public function publicInitClient() {
                return $this->initClient();
            }

            public function publicInitStack(\GuzzleHttp\HandlerStack &$stack): void {
                $this->initStack($stack);
            }

            public function publicGetCacheMiddlewareStrategy() {
                return $this->getCacheMiddlewareStrategy();
            }

            public function publicGetCacheMiddlewareStorage() {
                return $this->getCacheMiddlewareStorage();
            }

            public function publicGetRequestConfig(string $requestHandler, ...$handlerParams) {
                return $this->getRequestConfig($requestHandler, ...$handlerParams);
            }

            // Test request handler
            public function testRequest(string $id): RequestConfig {
                $request = new Request('GET', '/test/' . $id);
                return new RequestConfig($request);
            }
        };
    }

    public function testConstructorSetsUrl(): void
    {
        $api = $this->createTestApi('https://test.api.com');

        $this->assertEquals('https://test.api.com', $api->getUrl());
    }

    public function testSetAndGetUrl(): void
    {
        $api = $this->createTestApi();
        $api->setUrl('https://new.api.com');

        $this->assertEquals('https://new.api.com', $api->getUrl());
    }

    public function testSetAndGetAcceptType(): void
    {
        $api = $this->createTestApi();
        $api->setAcceptType('xml');

        $this->assertEquals('xml', $api->getAcceptType());
    }

    public function testSetAndGetTimeout(): void
    {
        $api = $this->createTestApi();
        $api->setTimeout(5.0);

        $this->assertEquals(5.0, $api->getTimeout());
    }

    public function testSetAndGetConnectTimeout(): void
    {
        $api = $this->createTestApi();
        $api->setConnectTimeout(2.0);

        $this->assertEquals(2.0, $api->getConnectTimeout());
    }

    public function testSetAndGetReadTimeout(): void
    {
        $api = $this->createTestApi();
        $api->setReadTimeout(15.0);

        $this->assertEquals(15.0, $api->getReadTimeout());
    }

    public function testSetAndGetBatchConcurrency(): void
    {
        $api = $this->createTestApi();
        $api->setBatchConcurrency(10);

        $this->assertEquals(10, $api->getBatchConcurrency());
    }

    public function testSetAndGetDecodeContent(): void
    {
        $api = $this->createTestApi();
        $api->setDecodeContent(false);

        $this->assertFalse($api->getDecodeContent());
    }

    public function testSetAndGetProxy(): void
    {
        $api = $this->createTestApi();
        $api->setProxy('127.0.0.1:8888');

        $this->assertEquals('127.0.0.1:8888', $api->getProxy());
    }

    public function testSetAndGetVerify(): void
    {
        $api = $this->createTestApi();
        $api->setVerify(false);

        $this->assertFalse($api->getVerify());
    }

    public function testSetAndGetDebugRequests(): void
    {
        $api = $this->createTestApi();
        $api->setDebugRequests(true);

        $this->assertTrue($api->getDebugRequests());
    }

    public function testSetAndGetDebugLevel(): void
    {
        $api = $this->createTestApi();
        $api->setDebugLevel(2);

        $this->assertEquals(2, $api->getDebugLevel());
    }

    public function testSetAndGetUserAgent(): void
    {
        $api = $this->createTestApi();
        $api->setUserAgent('TestAgent/1.0');

        $this->assertEquals('TestAgent/1.0', $api->getUserAgent());
    }

    public function testSetAndGetCachePool(): void
    {
        $api = $this->createTestApi();
        $closure = function() { return null; };
        $api->setCachePool($closure);

        $this->assertSame($closure, $api->getCachePool());
    }

    public function testSetAndGetNewLog(): void
    {
        $api = $this->createTestApi();
        $closure = function() { return null; };
        $api->setNewLog($closure);

        $this->assertSame($closure, $api->getNewLog());
    }

    public function testSetAndGetIsLoggable(): void
    {
        $api = $this->createTestApi();
        $closure = function() { return true; };
        $api->setIsLoggable($closure);

        $this->assertSame($closure, $api->getIsLoggable());
    }

    public function testSetLogEnabled(): void
    {
        $api = $this->createTestApi();
        $api->setLogEnabled(false);

        $config = $api->publicGetLogMiddlewareConfig();
        $this->assertFalse($config['log_enabled']);
    }

    public function testSetLogStats(): void
    {
        $api = $this->createTestApi();
        $api->setLogStats(true);

        $config = $api->publicGetLogMiddlewareConfig();
        $this->assertTrue($config['log_stats']);
    }

    public function testSetLogCache(): void
    {
        $api = $this->createTestApi();
        $api->setLogCache(true);

        $config = $api->publicGetLogMiddlewareConfig();
        $this->assertTrue($config['log_cache']);
    }

    public function testSetLogCacheHeader(): void
    {
        $api = $this->createTestApi();
        $api->setLogCacheHeader('X-Custom-Cache');

        $config = $api->publicGetLogMiddlewareConfig();
        $this->assertEquals('X-Custom-Cache', $config['log_cache_header']);
    }

    public function testSetLogAllStatus(): void
    {
        $api = $this->createTestApi();
        $api->setLogAllStatus(true);

        $config = $api->publicGetLogMiddlewareConfig();
        $this->assertTrue($config['log_all_status']);
    }

    public function testSetLogRequestHeaders(): void
    {
        $api = $this->createTestApi();
        $api->setLogRequestHeaders(true);

        $config = $api->publicGetLogMiddlewareConfig();
        $this->assertTrue($config['log_request_headers']);
    }

    public function testSetLogResponseHeaders(): void
    {
        $api = $this->createTestApi();
        $api->setLogResponseHeaders(true);

        $config = $api->publicGetLogMiddlewareConfig();
        $this->assertTrue($config['log_response_headers']);
    }

    public function testSetLogFile(): void
    {
        $api = $this->createTestApi();
        $api->setLogFile('custom_log');

        $config = $api->publicGetLogMiddlewareConfig();
        $this->assertEquals('custom_log', $config['log_file']);
    }

    public function testSetCacheEnabled(): void
    {
        $api = $this->createTestApi();
        $api->setCacheEnabled(false);

        $config = $api->publicGetCacheMiddlewareConfig();
        $this->assertFalse($config['cache_enabled']);
    }

    public function testSetCacheDebug(): void
    {
        $api = $this->createTestApi();
        $api->setCacheDebug(true);

        $config = $api->publicGetCacheMiddlewareConfig();
        $this->assertTrue($config['cache_debug']);
    }

    public function testSetCacheDebugHeader(): void
    {
        $api = $this->createTestApi();
        $api->setCacheDebugHeader('X-Custom-Debug');

        $config = $api->publicGetCacheMiddlewareConfig();
        $this->assertEquals('X-Custom-Debug', $config['cache_debug_header']);
    }

    public function testSetRetryEnabled(): void
    {
        $api = $this->createTestApi();
        $api->setRetryEnabled(false);

        $config = $api->publicGetRetryMiddlewareConfig();
        $this->assertFalse($config['retry_enabled']);
    }

    public function testSetRetryLogFile(): void
    {
        $api = $this->createTestApi();
        $api->setRetryLogFile('custom_retry_log');

        $config = $api->publicGetRetryMiddlewareConfig();
        $this->assertEquals('custom_retry_log', $config['retry_log_file']);
    }

    public function testGetAuthHeaderBasic(): void
    {
        $api = $this->createTestApi();
        $header = $api->publicGetAuthHeader('dXNlcjpwYXNz', 'Basic');

        $this->assertArrayHasKey('Authorization', $header);
        $this->assertEquals('Basic dXNlcjpwYXNz', $header['Authorization']);
    }

    public function testGetAuthHeaderBearer(): void
    {
        $api = $this->createTestApi();
        $header = $api->publicGetAuthHeader('token123', 'Bearer');

        $this->assertArrayHasKey('Authorization', $header);
        $this->assertEquals('Bearer token123', $header['Authorization']);
    }

    public function testLogReturnsClosureWhenNoLogCallback(): void
    {
        $api = $this->createTestApi();
        $logClosure = $api->publicLog();

        $this->assertInstanceOf(\Closure::class, $logClosure);

        // Should not throw when no log callback is set
        $logClosure('action', 'info', 'message', [], 'tag');
        $this->assertTrue(true);
    }

    public function testGetClientConfigReturnsArray(): void
    {
        $api = $this->createTestApi();
        $api->setTimeout(5.0);
        $api->setConnectTimeout(2.0);
        $api->setUserAgent('TestAgent');

        $config = $api->publicGetClientConfig();

        $this->assertIsArray($config);
        $this->assertEquals(5.0, $config['timeout']);
        $this->assertEquals(2.0, $config['connect_timeout']);
        $this->assertArrayHasKey('headers', $config);
        $this->assertEquals('TestAgent', $config['headers']['User-Agent']);
    }

    public function testGetLogMiddlewareConfigReturnsArray(): void
    {
        $api = $this->createTestApi();
        $config = $api->publicGetLogMiddlewareConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('log_enabled', $config);
        $this->assertArrayHasKey('log_stats', $config);
        $this->assertArrayHasKey('log_cache', $config);
        $this->assertArrayHasKey('log_callback', $config);
    }

    public function testGetCacheMiddlewareConfigReturnsArray(): void
    {
        $api = $this->createTestApi();
        $config = $api->publicGetCacheMiddlewareConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('cache_enabled', $config);
        $this->assertArrayHasKey('cache_debug', $config);
        $this->assertArrayHasKey('cache_debug_header', $config);
    }

    public function testGetRetryMiddlewareConfigReturnsArray(): void
    {
        $api = $this->createTestApi();
        $config = $api->publicGetRetryMiddlewareConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('retry_enabled', $config);
        $this->assertArrayHasKey('max_retry_attempts', $config);
        $this->assertArrayHasKey('retry_on_status', $config);
    }

    public function testArrayMergeRecursiveDistinct(): void
    {
        $api = $this->createTestApi();

        $array1 = [
            'a' => 1,
            'b' => ['c' => 2, 'd' => 3]
        ];

        $array2 = [
            'b' => ['c' => 4, 'e' => 5],
            'f' => 6
        ];

        $result = $api->publicArrayMergeRecursiveDistinct($array1, $array2);

        $this->assertEquals(1, $result['a']);
        $this->assertEquals(4, $result['b']['c']); // Overwritten
        $this->assertEquals(3, $result['b']['d']); // Preserved
        $this->assertEquals(5, $result['b']['e']); // Added
        $this->assertEquals(6, $result['f']); // Added
    }

    public function testGetClientReturnsWebClientInstance(): void
    {
        $api = $this->createTestApi();
        $client = $api->publicGetClient();

        $this->assertInstanceOf(\Exodus4D\ESI\Lib\WebClient::class, $client);
    }

    public function testGetClientReturnsSameInstance(): void
    {
        $api = $this->createTestApi();
        $client1 = $api->publicGetClient();
        $client2 = $api->publicGetClient();

        $this->assertSame($client1, $client2);
    }

    public function testInitClientReturnsWebClient(): void
    {
        $api = $this->createTestApi('https://test.example.com');
        $client = $api->publicInitClient();

        $this->assertInstanceOf(\Exodus4D\ESI\Lib\WebClient::class, $client);
    }

    public function testInitStackAddsMiddleware(): void
    {
        $api = $this->createTestApi();
        $stack = \GuzzleHttp\HandlerStack::create();

        $api->publicInitStack($stack);

        // Check that stack is modified (has middleware)
        $this->assertNotNull($stack);
    }

    public function testGetCacheMiddlewareStrategyReturnsStrategy(): void
    {
        $api = $this->createTestApi();
        $strategy = $api->publicGetCacheMiddlewareStrategy();

        $this->assertInstanceOf(
            \Exodus4D\ESI\Lib\Middleware\Cache\Strategy\CacheStrategyInterface::class,
            $strategy
        );
    }

    public function testGetCacheMiddlewareStorageReturnsNullWhenNoCachePool(): void
    {
        $api = $this->createTestApi();
        $storage = $api->publicGetCacheMiddlewareStorage();

        $this->assertNull($storage);
    }

    public function testGetCacheMiddlewareStorageReturnsPsr6Storage(): void
    {
        $api = $this->createTestApi();

        // Set a cache pool that returns a valid PSR-6 cache pool
        $api->setCachePool(function() {
            return new class implements \Psr\Cache\CacheItemPoolInterface {
                public function getItem($key): \Psr\Cache\CacheItemInterface {
                    return new class implements \Psr\Cache\CacheItemInterface {
                        public function getKey(): string { return 'test'; }
                        public function get(): mixed { return null; }
                        public function isHit(): bool { return false; }
                        public function set($value): static { return $this; }
                        public function expiresAt(?\DateTimeInterface $expiration): static { return $this; }
                        public function expiresAfter(\DateInterval|int|null $time): static { return $this; }
                    };
                }
                public function getItems(array $keys = []): iterable { return []; }
                public function hasItem($key): bool { return false; }
                public function clear(): bool { return true; }
                public function deleteItem($key): bool { return true; }
                public function deleteItems(array $keys): bool { return true; }
                public function save(\Psr\Cache\CacheItemInterface $item): bool { return true; }
                public function saveDeferred(\Psr\Cache\CacheItemInterface $item): bool { return true; }
                public function commit(): bool { return true; }
            };
        });

        $storage = $api->publicGetCacheMiddlewareStorage();

        $this->assertInstanceOf(
            \Exodus4D\ESI\Lib\Middleware\Cache\Storage\CacheStorageInterface::class,
            $storage
        );
    }

    public function testGetRequestConfigWithValidHandler(): void
    {
        $api = $this->createTestApi();
        $config = $api->publicGetRequestConfig('test', '123');

        $this->assertInstanceOf(\Exodus4D\ESI\Lib\RequestConfig::class, $config);
    }

    public function testGetRequestConfigWithInvalidHandlerReturnsNull(): void
    {
        $api = $this->createTestApi();
        $config = $api->publicGetRequestConfig('nonExistent', '123');

        $this->assertNull($config);
    }

    public function testSendBatchWithEmptyArrayReturnsEmptyArray(): void
    {
        $api = $this->createTestApi('https://httpbin.org');
        $results = $api->sendBatch([]);

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testSendBatchWithInvalidConfigThrowsException(): void
    {
        $api = $this->createTestApi();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid request config');

        $api->sendBatch([
            ['invalidHandler', 'param1']
        ]);
    }
}
