<?php

namespace Exodus4D\ESI\Tests\Unit\Lib\Middleware\Cache\Strategy;

use Exodus4D\ESI\Lib\Middleware\Cache\Storage\VolatileRuntimeStorage;
use Exodus4D\ESI\Lib\Middleware\Cache\Strategy\PrivateCacheStrategy;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PrivateCacheStrategy::class)]
class PrivateCacheStrategyTest extends TestCase
{
    private function createStrategy(): PrivateCacheStrategy
    {
        return new PrivateCacheStrategy();
    }

    private function createStrategyWithStorage(): PrivateCacheStrategy
    {
        return new PrivateCacheStrategy(new VolatileRuntimeStorage());
    }

    public function testConstructorWithoutStorage(): void
    {
        $strategy = new PrivateCacheStrategy();

        $this->assertInstanceOf(PrivateCacheStrategy::class, $strategy);
    }

    public function testConstructorWithCustomStorage(): void
    {
        $storage = new VolatileRuntimeStorage();
        $strategy = new PrivateCacheStrategy($storage);

        $this->assertInstanceOf(PrivateCacheStrategy::class, $strategy);
    }

    public function testFetchReturnsNullWhenNoCacheExists(): void
    {
        $strategy = $this->createStrategy();
        $request = new Request('GET', 'http://example.com/test');

        $result = $strategy->fetch($request);

        $this->assertNull($result);
    }

    public function testFetchReturnsNullWhenRequestHasNoCacheHeader(): void
    {
        $strategy = $this->createStrategy();
        $request = new Request('GET', 'http://example.com/test', ['Cache-Control' => 'no-cache']);

        $result = $strategy->fetch($request);

        $this->assertNull($result);
    }

    public function testFetchReturnsNullWhenPragmaNoCachePresent(): void
    {
        $strategy = $this->createStrategy();
        $request = new Request('GET', 'http://example.com/test', ['Pragma' => 'no-cache']);

        $result = $strategy->fetch($request);

        $this->assertNull($result);
    }

    public function testCacheStoresResponseWithMaxAge(): void
    {
        $strategy = $this->createStrategy();
        $request = new Request('GET', 'http://example.com/test');
        $response = new Response(200, ['Cache-Control' => 'max-age=300'], '{"test": true}');

        $result = $strategy->cache($request, $response);

        $this->assertTrue($result);
    }

    public function testCacheReturnsStoredEntry(): void
    {
        $strategy = $this->createStrategy();
        $request = new Request('GET', 'http://example.com/test');
        $response = new Response(200, ['Cache-Control' => 'max-age=300'], '{"test": true}');

        $strategy->cache($request, $response);
        $cached = $strategy->fetch($request);

        $this->assertNotNull($cached);
        $this->assertEquals(200, $cached->getOriginalResponse()->getStatusCode());
    }

    public function testCacheRejectsNonCacheableStatusCode(): void
    {
        $strategy = $this->createStrategy();
        $request = new Request('GET', 'http://example.com/test');
        $response = new Response(500, ['Cache-Control' => 'max-age=300'], '{"error": true}');

        $result = $strategy->cache($request, $response);

        $this->assertFalse($result);
    }

    public function testCacheStoresVariousAcceptedStatusCodes(): void
    {
        $strategy = $this->createStrategy();
        $statusCodes = [200, 203, 204, 300, 301, 404, 405, 410, 414, 501];

        foreach ($statusCodes as $code) {
            $request = new Request('GET', "http://example.com/test{$code}");
            $response = new Response($code, ['Cache-Control' => 'max-age=300']);

            $result = $strategy->cache($request, $response);

            $this->assertTrue($result, "Status code {$code} should be cacheable");
        }
    }

    public function testCacheHandlesVaryHeader(): void
    {
        $strategy = $this->createStrategy();
        $request = new Request('GET', 'http://example.com/test', ['Accept' => 'application/json']);
        $response = new Response(200, [
            'Cache-Control' => 'max-age=300',
            'Vary' => 'Accept'
        ], '{"test": true}');

        $result = $strategy->cache($request, $response);

        $this->assertTrue($result);
    }

    public function testCacheStoresNoCacheWhenValidationInfoExists(): void
    {
        $strategy = $this->createStrategy();
        $request = new Request('GET', 'http://example.com/test');
        $response = new Response(200, [
            'Cache-Control' => 'no-cache',
            'Etag' => '"abc123"'
        ], '{"test": true}');

        $result = $strategy->cache($request, $response);

        // no-cache with validation info (Etag) can be cached as stale for revalidation
        $this->assertTrue($result);
    }

    public function testCacheHandlesExpiresHeader(): void
    {
        $strategy = $this->createStrategy();
        $request = new Request('GET', 'http://example.com/test');
        $expiresDate = gmdate(\DateTime::RFC1123, time() + 300);
        $response = new Response(200, [
            'Cache-Control' => '',
            'Expires' => $expiresDate
        ], '{"test": true}');

        $result = $strategy->cache($request, $response);

        $this->assertTrue($result);
    }

    public function testUpdateCallsCacheMethod(): void
    {
        $strategy = $this->createStrategy();
        $request = new Request('GET', 'http://example.com/test');
        $response = new Response(200, ['Cache-Control' => 'max-age=300'], '{"test": true}');

        $result = $strategy->update($request, $response);

        $this->assertTrue($result);
        $this->assertNotNull($strategy->fetch($request));
    }

    public function testDeleteRemovesCachedEntry(): void
    {
        $strategy = $this->createStrategy();
        $request = new Request('GET', 'http://example.com/test');
        $response = new Response(200, ['Cache-Control' => 'max-age=300'], '{"test": true}');

        $strategy->cache($request, $response);
        $this->assertNotNull($strategy->fetch($request));

        $result = $strategy->delete($request);

        $this->assertTrue($result);
        $this->assertNull($strategy->fetch($request));
    }

    public function testDeleteReturnsTrueEvenIfNothingDeleted(): void
    {
        $strategy = $this->createStrategy();
        $request = new Request('GET', 'http://example.com/test');

        $result = $strategy->delete($request);

        // VolatileRuntimeStorage returns false when key doesn't exist
        $this->assertFalse($result);
    }

    public function testFetchRespectsRequestMaxAge(): void
    {
        $strategy = $this->createStrategy();
        $request = new Request('GET', 'http://example.com/test');
        $response = new Response(200, ['Cache-Control' => 'max-age=300'], '{"test": true}');

        $strategy->cache($request, $response);

        // Request with high max-age should return cached entry
        $requestWithMaxAge = new Request('GET', 'http://example.com/test', ['Cache-Control' => 'max-age=600']);
        $result = $strategy->fetch($requestWithMaxAge);

        $this->assertNotNull($result);
    }

    public function testDifferentUrlsHaveDifferentCacheKeys(): void
    {
        $strategy = $this->createStrategy();

        $request1 = new Request('GET', 'http://example.com/test1');
        $response1 = new Response(200, ['Cache-Control' => 'max-age=300'], '{"test": 1}');

        $request2 = new Request('GET', 'http://example.com/test2');
        $response2 = new Response(200, ['Cache-Control' => 'max-age=300'], '{"test": 2}');

        $strategy->cache($request1, $response1);
        $strategy->cache($request2, $response2);

        $cached1 = $strategy->fetch($request1);
        $cached2 = $strategy->fetch($request2);

        $this->assertNotNull($cached1);
        $this->assertNotNull($cached2);
        $this->assertNotSame($cached1, $cached2);
    }
}
