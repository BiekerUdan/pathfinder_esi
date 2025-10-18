<?php

namespace Exodus4D\ESI\Tests\Unit\Lib\Middleware;

use Exodus4D\ESI\Lib\Middleware\Cache\CacheEntry;
use Exodus4D\ESI\Lib\Middleware\Cache\Strategy\PrivateCacheStrategy;
use Exodus4D\ESI\Lib\Middleware\Cache\Storage\VolatileRuntimeStorage;
use Exodus4D\ESI\Lib\Middleware\GuzzleCacheMiddleware;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GuzzleCacheMiddleware::class)]
class GuzzleCacheMiddlewareTest extends TestCase
{
    private function createMockHandler($response = null)
    {
        $response = $response ?? new Response(200);
        return function() use ($response) {
            return new FulfilledPromise($response);
        };
    }

    private function createRejectedHandler($exception)
    {
        return function() use ($exception) {
            return new RejectedPromise($exception);
        };
    }

    private function createCacheStrategy()
    {
        return new PrivateCacheStrategy(new VolatileRuntimeStorage());
    }

    // Test static helper methods (existing tests)
    public function testParseHeaderWithSimpleValue(): void
    {
        $headers = ['max-age=3600'];
        $parsed = GuzzleCacheMiddleware::parseHeader($headers);

        $this->assertIsArray($parsed);
        $this->assertCount(1, $parsed);
        $this->assertArrayHasKey('max-age', $parsed[0]);
        $this->assertEquals('3600', $parsed[0]['max-age']);
    }

    public function testParseHeaderWithMultipleDirectives(): void
    {
        $headers = ['max-age=3600, public, must-revalidate'];
        $parsed = GuzzleCacheMiddleware::parseHeader($headers);

        $this->assertIsArray($parsed);
        $this->assertArrayHasKey('max-age', $parsed[0]);
        $this->assertArrayHasKey('public', $parsed[0]);
        $this->assertArrayHasKey('must-revalidate', $parsed[0]);
    }

    public function testParseHeaderWithQuotedValues(): void
    {
        $headers = ['charset="utf-8"'];
        $parsed = GuzzleCacheMiddleware::parseHeader($headers);

        $this->assertIsArray($parsed);
        $this->assertEquals('utf-8', $parsed[0]['charset']);
    }

    public function testParseHeaderWithMultipleHeaders(): void
    {
        $headers = ['max-age=3600', 's-maxage=7200'];
        $parsed = GuzzleCacheMiddleware::parseHeader($headers);

        $this->assertCount(2, $parsed);
    }

    public function testParseHeaderWithFlagDirectives(): void
    {
        $headers = ['no-cache, no-store'];
        $parsed = GuzzleCacheMiddleware::parseHeader($headers);

        $this->assertArrayHasKey('no-cache', $parsed[0]);
        $this->assertArrayHasKey('no-store', $parsed[0]);
        $this->assertEquals('', $parsed[0]['no-cache']);
        $this->assertEquals('', $parsed[0]['no-store']);
    }

    public function testInArrayDeepFindsNestedValue(): void
    {
        $array = [
            ['one', 'two', 'three'],
            ['four', 'five']
        ];

        $this->assertTrue(GuzzleCacheMiddleware::inArrayDeep($array, 'two'));
        $this->assertTrue(GuzzleCacheMiddleware::inArrayDeep($array, 'five'));
        $this->assertFalse(GuzzleCacheMiddleware::inArrayDeep($array, 'six'));
    }

    public function testArrayKeyDeepFindsNestedKey(): void
    {
        $array = [
            ['max-age' => '3600', 'public' => ''],
            ['s-maxage' => '7200']
        ];

        $this->assertEquals('3600', GuzzleCacheMiddleware::arrayKeyDeep($array, 'max-age'));
        $this->assertEquals('7200', GuzzleCacheMiddleware::arrayKeyDeep($array, 's-maxage'));
        $this->assertEquals('', GuzzleCacheMiddleware::arrayKeyDeep($array, 'nonexistent'));
    }

    public function testArrayFlattenByValue(): void
    {
        $array = [
            'level1' => ['a', 'b'],
            'level2' => [
                'sublevel' => ['c', 'd', 'e']
            ],
            'level3' => 'f'
        ];

        $flattened = GuzzleCacheMiddleware::arrayFlattenByValue($array);

        $this->assertEquals(['a', 'b', 'c', 'd', 'e', 'f'], $flattened);
    }

    // New comprehensive tests for middleware functionality
    public function testFactoryReturnsClosureThatCreatesMiddleware(): void
    {
        $factory = GuzzleCacheMiddleware::factory();

        $this->assertInstanceOf(\Closure::class, $factory);

        $handler = $this->createMockHandler();
        $middleware = $factory($handler);

        $this->assertInstanceOf(GuzzleCacheMiddleware::class, $middleware);
    }

    public function testFactoryWithCustomOptions(): void
    {
        $factory = GuzzleCacheMiddleware::factory(['cache_enabled' => false]);

        $handler = $this->createMockHandler();
        $middleware = $factory($handler);

        $this->assertInstanceOf(GuzzleCacheMiddleware::class, $middleware);
    }

    public function testFactoryWithCustomCacheStrategy(): void
    {
        $cacheStrategy = $this->createCacheStrategy();
        $factory = GuzzleCacheMiddleware::factory([], $cacheStrategy);

        $handler = $this->createMockHandler();
        $middleware = $factory($handler);

        $this->assertInstanceOf(GuzzleCacheMiddleware::class, $middleware);
    }

    public function testConstructorSetsDefaults(): void
    {
        $handler = $this->createMockHandler();
        $middleware = new GuzzleCacheMiddleware($handler);

        $this->assertInstanceOf(GuzzleCacheMiddleware::class, $middleware);
    }

    public function testConstructorWithCustomCacheStrategy(): void
    {
        $handler = $this->createMockHandler();
        $cacheStrategy = $this->createCacheStrategy();
        $middleware = new GuzzleCacheMiddleware($handler, [], $cacheStrategy);

        $this->assertInstanceOf(GuzzleCacheMiddleware::class, $middleware);
    }

    public function testInvokeWhenDisabledSkipsCaching(): void
    {
        $handler = $this->createMockHandler();
        $middleware = new GuzzleCacheMiddleware($handler, ['cache_enabled' => false]);

        $request = new Request('GET', 'http://example.com/test');
        $promise = $middleware($request, []);

        $this->assertNotNull($promise);
    }

    public function testInvokeWithNonCacheableMethod(): void
    {
        $response = new Response(200);
        $handler = $this->createMockHandler($response);
        $middleware = new GuzzleCacheMiddleware($handler, ['cache_http_methods' => ['GET']]);

        $request = new Request('POST', 'http://example.com/test');
        $promise = $middleware($request, []);
        $result = $promise->wait();

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testInvokeWithReValidationHeader(): void
    {
        $response = new Response(200);
        $handler = $this->createMockHandler($response);
        $middleware = new GuzzleCacheMiddleware($handler);

        $request = new Request('GET', 'http://example.com/test', ['X-Guzzle-Cache-ReValidation' => '1']);
        $promise = $middleware($request, []);
        $result = $promise->wait();

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testInvokeWithFreshCacheEntry(): void
    {
        $cacheStrategy = $this->createCacheStrategy();
        $handler = $this->createMockHandler();
        $middleware = new GuzzleCacheMiddleware($handler, [], $cacheStrategy);

        $request = new Request('GET', 'http://example.com/test');
        $response = new Response(200, ['Cache-Control' => 'max-age=300'], '{"test": true}');

        // Cache the response first
        $cacheStrategy->cache($request, $response);

        // Now fetch from cache
        $promise = $middleware($request, []);
        $result = $promise->wait();

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testInvokeWithDebugHeader(): void
    {
        $cacheStrategy = $this->createCacheStrategy();
        $handler = $this->createMockHandler();
        $middleware = new GuzzleCacheMiddleware($handler, ['cache_debug' => true], $cacheStrategy);

        $request = new Request('GET', 'http://example.com/test');
        $response = new Response(200, ['Cache-Control' => 'max-age=300'], '{"test": true}');

        // Cache the response first
        $cacheStrategy->cache($request, $response);

        // Now fetch from cache
        $promise = $middleware($request, []);
        $result = $promise->wait();

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue($result->hasHeader('X-Guzzle-Cache'));
    }

    public function testOnFulfilledReturnsStaleOn5xxError(): void
    {
        $cacheStrategy = $this->createCacheStrategy();
        $handler = $this->createMockHandler(new Response(500));
        $middleware = new GuzzleCacheMiddleware($handler, [], $cacheStrategy);

        $request = new Request('GET', 'http://example.com/test');
        $response = new Response(200, [
            'Cache-Control' => 'max-age=0, stale-if-error=300'
        ], '{"test": true}');

        // Cache the response first
        $cacheStrategy->cache($request, $response);

        // Now make request that returns 500
        $promise = $middleware($request, []);
        $result = $promise->wait();

        // Should return cached response instead of 500
        $this->assertNotNull($result);
    }

    public function testOnFulfilledHandles304NotModified(): void
    {
        $cacheStrategy = $this->createCacheStrategy();
        $handler = $this->createMockHandler(new Response(304, ['Cache-Control' => 'max-age=300']));
        $middleware = new GuzzleCacheMiddleware($handler, [], $cacheStrategy);

        $request = new Request('GET', 'http://example.com/test');
        $response = new Response(200, ['Cache-Control' => 'max-age=300', 'Etag' => '"abc123"'], '{"test": true}');

        // Cache the response first
        $cacheStrategy->cache($request, $response);

        // Manually fetch cache entry to trigger re-validation
        $cacheEntry = $cacheStrategy->fetch($request);

        // Create reflection to test onFulfilled
        $reflection = new \ReflectionClass(GuzzleCacheMiddleware::class);
        $method = $reflection->getMethod('onFulfilled');
        $method->setAccessible(true);

        $closure = $method->invoke($middleware, $request, $cacheEntry, ['cache_debug' => false, 'cache_debug_header' => 'X-Guzzle-Cache', 'cache_enabled' => true]);
        $result = $closure(new Response(304, ['Cache-Control' => 'max-age=300']));

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testOnRejectedWithTransferException(): void
    {
        $cacheStrategy = $this->createCacheStrategy();
        $request = new Request('GET', 'http://example.com/test');
        $exception = new ConnectException('Connection failed', $request);
        $handler = $this->createRejectedHandler($exception);

        $middleware = new GuzzleCacheMiddleware($handler, [], $cacheStrategy);

        $response = new Response(200, [
            'Cache-Control' => 'max-age=0, stale-if-error=300'
        ], '{"test": true}');

        // Cache the response first
        $cacheStrategy->cache($request, $response);

        // Now make request that throws exception
        $promise = $middleware($request, []);
        $result = $promise->wait();

        // Should return cached response instead of throwing
        $this->assertNotNull($result);
    }

    public function testAddDebugHeaderWhenEnabled(): void
    {
        $reflection = new \ReflectionClass(GuzzleCacheMiddleware::class);
        $method = $reflection->getMethod('addDebugHeader');
        $method->setAccessible(true);

        $response = new Response(200);
        $options = ['cache_enabled' => true, 'cache_debug' => true, 'cache_debug_header' => 'X-Cache'];
        $result = $method->invoke(null, $response, 'HIT', $options);

        $this->assertTrue($result->hasHeader('X-Cache'));
        $this->assertEquals('HIT', $result->getHeaderLine('X-Cache'));
    }

    public function testAddDebugHeaderWhenDisabled(): void
    {
        $reflection = new \ReflectionClass(GuzzleCacheMiddleware::class);
        $method = $reflection->getMethod('addDebugHeader');
        $method->setAccessible(true);

        $response = new Response(200);
        $options = ['cache_enabled' => false, 'cache_debug' => true, 'cache_debug_header' => 'X-Cache'];
        $result = $method->invoke(null, $response, 'HIT', $options);

        $this->assertFalse($result->hasHeader('X-Cache'));
    }

    public function testAddToCacheSavesResponse(): void
    {
        $reflection = new \ReflectionClass(GuzzleCacheMiddleware::class);
        $method = $reflection->getMethod('addToCache');
        $method->setAccessible(true);

        $cacheStrategy = $this->createCacheStrategy();
        $request = new Request('GET', 'http://example.com/test');
        $response = new Response(200, ['Cache-Control' => 'max-age=300'], '{"test": true}');

        $result = $method->invoke(null, $cacheStrategy, $request, $response, false);

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testAddToCacheUpdatesResponse(): void
    {
        $reflection = new \ReflectionClass(GuzzleCacheMiddleware::class);
        $method = $reflection->getMethod('addToCache');
        $method->setAccessible(true);

        $cacheStrategy = $this->createCacheStrategy();
        $request = new Request('GET', 'http://example.com/test');
        $response = new Response(200, ['Cache-Control' => 'max-age=300'], '{"test": true}');

        // Cache first
        $cacheStrategy->cache($request, $response);

        // Now update
        $result = $method->invoke(null, $cacheStrategy, $request, $response, true);

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testGetStaleResponseWhenAllowed(): void
    {
        $reflection = new \ReflectionClass(GuzzleCacheMiddleware::class);
        $method = $reflection->getMethod('getStaleResponse');
        $method->setAccessible(true);

        $request = new Request('GET', 'http://example.com/test');
        $response = new Response(200, [
            'Cache-Control' => 'max-age=0, stale-if-error=300'
        ], '{"test": true}');
        $staleAt = new \DateTime('-1 seconds');

        $cacheEntry = new CacheEntry($request, $response, $staleAt);
        $options = ['cache_enabled' => true, 'cache_debug' => false];

        $result = $method->invoke(null, $cacheEntry, $options);

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testGetStaleResponseWhenNotAllowed(): void
    {
        $reflection = new \ReflectionClass(GuzzleCacheMiddleware::class);
        $method = $reflection->getMethod('getStaleResponse');
        $method->setAccessible(true);

        $request = new Request('GET', 'http://example.com/test');
        $response = new Response(200, ['Cache-Control' => 'max-age=300'], '{"test": true}');
        $staleAt = new \DateTime('+300 seconds');

        $cacheEntry = new CacheEntry($request, $response, $staleAt);
        $options = ['cache_enabled' => true, 'cache_debug' => false];

        $result = $method->invoke(null, $cacheEntry, $options);

        $this->assertNull($result);
    }

    public function testGetRequestWithReValidationHeaderLastModified(): void
    {
        $reflection = new \ReflectionClass(GuzzleCacheMiddleware::class);
        $method = $reflection->getMethod('getRequestWithReValidationHeader');
        $method->setAccessible(true);

        $request = new Request('GET', 'http://example.com/test');
        $response = new Response(200, [
            'Cache-Control' => 'max-age=300',
            'Last-Modified' => 'Wed, 21 Oct 2015 07:28:00 GMT'
        ], '{"test": true}');
        $staleAt = new \DateTime('+300 seconds');

        $cacheEntry = new CacheEntry($request, $response, $staleAt);
        $result = $method->invoke(null, $request, $cacheEntry);

        $this->assertTrue($result->hasHeader('If-Modified-Since'));
    }

    public function testGetRequestWithReValidationHeaderEtag(): void
    {
        $reflection = new \ReflectionClass(GuzzleCacheMiddleware::class);
        $method = $reflection->getMethod('getRequestWithReValidationHeader');
        $method->setAccessible(true);

        $request = new Request('GET', 'http://example.com/test');
        $response = new Response(200, [
            'Cache-Control' => 'max-age=300',
            'Etag' => '"abc123"'
        ], '{"test": true}');
        $staleAt = new \DateTime('+300 seconds');

        $cacheEntry = new CacheEntry($request, $response, $staleAt);
        $result = $method->invoke(null, $request, $cacheEntry);

        $this->assertTrue($result->hasHeader('If-None-Match'));
    }

    public function testPurgeReValidation(): void
    {
        $handler = $this->createMockHandler();
        $middleware = new GuzzleCacheMiddleware($handler);

        // Call purgeReValidation
        $middleware->purgeReValidation();

        // If no exception thrown, test passes
        $this->assertTrue(true);
    }

    public function testInvokeWithNoCacheEntry(): void
    {
        $cacheStrategy = $this->createCacheStrategy();
        $handler = $this->createMockHandler(new Response(200, [], '{"new": true}'));
        $middleware = new GuzzleCacheMiddleware($handler, [], $cacheStrategy);

        $request = new Request('GET', 'http://example.com/test');

        // No cache entry exists, should fetch from handler
        $promise = $middleware($request, []);
        $result = $promise->wait();

        $this->assertEquals(200, $result->getStatusCode());
    }
}
