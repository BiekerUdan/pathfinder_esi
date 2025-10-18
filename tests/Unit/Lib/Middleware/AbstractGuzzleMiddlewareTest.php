<?php

namespace Exodus4D\ESI\Tests\Unit\Lib\Middleware;

use Cache\Adapter\Void\VoidCachePool;
use Exodus4D\ESI\Lib\Middleware\AbstractGuzzleMiddleware;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;

#[CoversClass(AbstractGuzzleMiddleware::class)]
class AbstractGuzzleMiddlewareTest extends TestCase
{
    private function createConcreteMiddleware(): AbstractGuzzleMiddleware
    {
        return new class extends AbstractGuzzleMiddleware {
            // Concrete implementation for testing
            public function publicCache(): CacheItemPoolInterface {
                return $this->cache();
            }

            public function publicCacheKeyFromRequestUrl(Request $request, string $tag = ''): string {
                return $this->cacheKeyFromRequestUrl($request, $tag);
            }

            public function publicCacheKeyFromUrl(string $url, string $tag = ''): string {
                return $this->cacheKeyFromUrl($url, $tag);
            }

            public function publicGetNormalizedUrl(string $url): string {
                return $this->getNormalizedUrl($url);
            }

            public function publicHashKey(string $key): string {
                return $this->hashKey($key);
            }
        };
    }

    public function testInvokeWithoutCachePoolOption(): void
    {
        $middleware = $this->createConcreteMiddleware();
        $request = new Request('GET', 'http://example.com/test');

        $middleware($request, []);

        // Should use VoidCachePool by default
        $this->assertInstanceOf(VoidCachePool::class, $middleware->publicCache());
    }

    public function testInvokeWithCachePoolOption(): void
    {
        $middleware = $this->createConcreteMiddleware();
        $request = new Request('GET', 'http://example.com/test');
        $customPool = new VoidCachePool();

        $options = [
            'get_cache_pool' => function() use ($customPool) {
                return $customPool;
            }
        ];

        $middleware($request, $options);

        $cache = $middleware->publicCache();
        $this->assertSame($customPool, $cache);
    }

    public function testCacheReturnsVoidCachePoolByDefault(): void
    {
        $middleware = $this->createConcreteMiddleware();
        $request = new Request('GET', 'http://example.com/test');

        $middleware($request, []);

        $cache = $middleware->publicCache();
        $this->assertInstanceOf(VoidCachePool::class, $cache);
    }

    public function testCacheKeyFromRequestUrl(): void
    {
        $middleware = $this->createConcreteMiddleware();
        $request = new Request('GET', 'http://example.com/test/123');

        $middleware($request, []);

        $key = $middleware->publicCacheKeyFromRequestUrl($request);

        $this->assertIsString($key);
        $this->assertEquals(40, strlen($key)); // SHA1 hash is 40 characters
    }

    public function testCacheKeyFromRequestUrlWithTag(): void
    {
        $middleware = $this->createConcreteMiddleware();
        $request = new Request('GET', 'http://example.com/test/123');

        $middleware($request, []);

        $keyWithoutTag = $middleware->publicCacheKeyFromRequestUrl($request);
        $keyWithTag = $middleware->publicCacheKeyFromRequestUrl($request, 'tag1');

        $this->assertNotEquals($keyWithoutTag, $keyWithTag);
    }

    public function testCacheKeyFromUrl(): void
    {
        $middleware = $this->createConcreteMiddleware();
        $request = new Request('GET', 'http://example.com/test');

        $middleware($request, []);

        $key = $middleware->publicCacheKeyFromUrl('http://example.com/test/123');

        $this->assertIsString($key);
        $this->assertEquals(40, strlen($key)); // SHA1 hash is 40 characters
    }

    public function testCacheKeyFromUrlWithTag(): void
    {
        $middleware = $this->createConcreteMiddleware();
        $request = new Request('GET', 'http://example.com/test');

        $middleware($request, []);

        $keyWithoutTag = $middleware->publicCacheKeyFromUrl('http://example.com/test/123');
        $keyWithTag = $middleware->publicCacheKeyFromUrl('http://example.com/test/123', 'tag1');

        $this->assertNotEquals($keyWithoutTag, $keyWithTag);
    }

    public function testGetNormalizedUrlReplacesNumericIds(): void
    {
        $middleware = $this->createConcreteMiddleware();
        $request = new Request('GET', 'http://example.com/test');

        $middleware($request, []);

        $normalized = $middleware->publicGetNormalizedUrl('http://example.com/characters/12345/assets');

        $this->assertEquals('http://example.com/characters/x/assets', $normalized);
    }

    public function testGetNormalizedUrlHandlesMultipleIds(): void
    {
        $middleware = $this->createConcreteMiddleware();
        $request = new Request('GET', 'http://example.com/test');

        $middleware($request, []);

        $normalized = $middleware->publicGetNormalizedUrl('http://example.com/universe/12345/systems/67890/planets');

        $this->assertEquals('http://example.com/universe/x/systems/x/planets', $normalized);
    }

    public function testHashKeyReturnsSha1Hash(): void
    {
        $middleware = $this->createConcreteMiddleware();
        $request = new Request('GET', 'http://example.com/test');

        $middleware($request, []);

        $hash = $middleware->publicHashKey('test-key');

        $this->assertEquals(sha1('test-key'), $hash);
        $this->assertEquals(40, strlen($hash));
    }

    public function testHashKeyIsConsistent(): void
    {
        $middleware = $this->createConcreteMiddleware();
        $request = new Request('GET', 'http://example.com/test');

        $middleware($request, []);

        $hash1 = $middleware->publicHashKey('test-key');
        $hash2 = $middleware->publicHashKey('test-key');

        $this->assertEquals($hash1, $hash2);
    }
}
